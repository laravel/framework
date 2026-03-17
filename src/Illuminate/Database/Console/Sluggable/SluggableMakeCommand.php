<?php

namespace Illuminate\Database\Console\Sluggable;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function Illuminate\Filesystem\join_paths;

#[AsCommand(name: 'make:sluggable', description: 'Add the Sluggable attribute to a model and create a migration for the slug column')]
class SluggableMakeCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:sluggable {model : The model to make sluggable}
                                       {--from= : The source column to generate the slug from}
                                       {--to=slug : The target column to place the slug value}';

    /**
     * Create a new command instance.
     */
    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelClass = $this->qualifyModel($this->argument('model'));
        $modelPath = $this->getModelPath($modelClass);

        if (! $this->files->exists($modelPath)) {
            $this->components->error("Model [{$modelClass}] does not exist.");

            return 1;
        }

        $model = $this->laravel->make($modelClass);
        $table = $model->getTable();
        $column = $this->option('to');
        $source = $this->option('from') ?? $this->guessSourceColumn($model);

        $this->addAttributeToModel($modelClass, $modelPath, $source, $column);

        return $this->createMigration($table, $column);
    }

    /**
     * Add the Sluggable attribute to the model file.
     */
    protected function addAttributeToModel(string $modelClass, string $modelPath, string $source, string $column): void
    {
        $contents = $this->files->get($modelPath);

        if (str_contains($contents, '#[Sluggable')) {
            $this->components->warn("Model [{$modelClass}] already has the Sluggable attribute.");

            return;
        }

        $import = 'use Illuminate\\Database\\Eloquent\\Attributes\\Sluggable;';

        if (! str_contains($contents, $import)) {
            preg_match_all('/^use [^;]+;/m', $contents, $matches, PREG_OFFSET_CAPTURE);

            if (! empty($matches[0])) {
                $lastMatch = end($matches[0]);
                $position = $lastMatch[1] + strlen($lastMatch[0]);
                $contents = substr_replace($contents, "\n".$import, $position, 0);
            }
        }

        $attribute = $column === 'slug'
            ? "#[Sluggable(from: '{$source}')]"
            : "#[Sluggable(from: '{$source}', to: '{$column}')]";

        $contents = preg_replace(
            '/(^)(class\s+\w+)/m',
            "$1{$attribute}\n$2",
            $contents,
            1
        );

        $this->files->put($modelPath, $contents);

        $this->components->info("Sluggable attribute added to [{$modelClass}].");
    }

    /**
     * Guess the source column for the slug from the model's table.
     */
    protected function guessSourceColumn(Model $model): string
    {
        try {
            $columns = $this->laravel['db']->connection()
                ->getSchemaBuilder()
                ->getColumnListing($model->getTable());
        } catch (QueryException) {
            return 'name';
        }

        return collect(['name', 'title', 'headline', 'subject'])
            ->first(fn ($candidate) => in_array($candidate, $columns, true), 'name');
    }

    /**
     * Create the migration file for the slug column.
     */
    protected function createMigration(string $table, string $column): int
    {
        if ($this->hasSlugColumn($table, $column)) {
            $this->components->warn("Table [{$table}] already has a [{$column}] column. Migration not created.");

            return 0;
        }

        if ($this->migrationExists($table, $column)) {
            $this->components->error('Migration already exists.');

            return 1;
        }

        $path = $this->laravel['migration.creator']->create(
            "add_{$column}_to_{$table}_table",
            $this->laravel->databasePath('/migrations')
        );

        $stub = str_replace(
            ['{{table}}', '{{column}}'], [$table, $column], $this->files->get(__DIR__.'/stubs/add_slug_column.stub')
        );

        $this->files->put($path, $stub);

        $this->components->warn('Migration created. Please review it before running — you may need to adjust it based on your existing data or slug configuration.');

        return 0;
    }

    /**
     * Determine whether the table already has a slug column.
     */
    protected function hasSlugColumn(string $table, string $column): bool
    {
        try {
            return $this->laravel['db']->connection()
                ->getSchemaBuilder()
                ->hasColumn($table, $column);
        } catch (QueryException) {
            return false;
        }
    }

    /**
     * Determine whether a migration for the slug column already exists.
     */
    protected function migrationExists(string $table, string $column): bool
    {
        return count($this->files->glob(
            join_paths($this->laravel->databasePath('migrations'), "*_*_*_*_add_{$column}_to_{$table}_table.php")
        )) !== 0;
    }

    /**
     * Get the file path for the given model class.
     */
    protected function getModelPath(string $modelClass): string
    {
        $relativePath = str_replace('\\', '/', Str::replaceFirst($this->laravel->getNamespace(), '', $modelClass));

        return app_path($relativePath.'.php');
    }

    /**
     * Qualify the given model class base name.
     */
    protected function qualifyModel(string $model): string
    {
        $model = ltrim($model, '\\/');

        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->laravel->getNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return is_dir(app_path('Models'))
            ? $rootNamespace.'Models\\'.$model
            : $rootNamespace.$model;
    }
}
