<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:dto')]
class DtoMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:dto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new DTO class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'DTO';

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        if (! $this->option('model')) {
            $this->components->error('Model does not specify. ex: --model=User');

            return false;
        }

        return $this->createDtoFromModel();
    }

    /**
     * Create DTO from model.
     *
     * @return bool
     */
    protected function createDtoFromModel()
    {
        $model = $this->option('model');
        $modelClass = $this->qualifyModel($model);

        if (! class_exists($modelClass)) {
            $this->components->error("Model [{$modelClass}] does not exist.");
            return false;
        }

        $properties = $this->getModelProperties($modelClass);

        if (empty($properties)) {
            $this->components->error('No properties found for the model.');
            return false;
        }

        $dtoName = $this->getNameInput();
        $name = $this->rootNamespace().'DataTransferObjects\\'.$dtoName;
        $path = app_path('DataTransferObjects'.DIRECTORY_SEPARATOR.$dtoName.'.php');

        if (file_exists($path) && ! $this->option('force')) {
            $this->components->error($this->type.' already exists.');
            return false;
        }

        $this->makeDirectory($path);
        $this->files->put($path, $this->buildDtoClass($name, $properties));
        $this->components->info(sprintf('%s [%s] created successfully.', $this->type, $path));
        return true;
    }

    /**
     * Get model properties from database or migration files.
     *
     * @param  string  $modelClass
     * @return array
     */
    protected function getModelProperties($modelClass)
    {
        $instance = new $modelClass;
        $table = $instance->getTable();
        $columns = $this->laravel['db']->getSchemaBuilder()->getColumns($table);

        if (count($columns) > 0) {
            return collect($columns)
                ->map(fn ($col) => [
                    'name' => $col['name'],
                    'type' => $this->mapDatabaseTypeToPhpType($col['type_name']),
                    'nullable' => $col['nullable'],
                ])
                ->reject(fn ($col) => in_array($col['name'], ['created_at', 'updated_at', 'deleted_at']))
                ->values()
                ->all();
        } else {
            $this->components->warn('Could not read from database. Parsing migration files...');
            return $this->getPropertiesFromMigration($table);
        }
    }

    /**
     * Get properties from migration files.
     *
     * @param  string  $table
     * @return array
     */
    protected function getPropertiesFromMigration($table)
    {
        $migrationFiles = glob(database_path('migrations/*.php'));

        if (empty($migrationFiles)) {
            return [];
        }

        foreach ($migrationFiles as $file) {
            $content = file_get_contents($file);
            if (preg_match("/Schema::create\(['\"]".preg_quote($table)."['\"]/", $content)) {
                return $this->parseMigrationSchema($content);
            }
        }

        return [];
    }

    /**
     * Parse migration schema to extract column definitions.
     *
     * @param  string  $content
     * @return array
     */
    protected function parseMigrationSchema($content)
    {
        $properties = [];

        preg_match('/Schema::create\([\'"](\w+)[\'"]\s*,\s*function\s*\(\s*Blueprint\s+\$table\s*\)\s*\{(.*?)\}\);/s', $content, $matches);
        if (! isset($matches[2])) {
            return [];
        }
        $schemaContent = $matches[2];

        preg_match_all('/\$table->(\w+)\(\s*[\'"](\w+)[\'"]\s*\)((?:->nullable\(\))?(?:->default\([^)]*\))?(?:->comment\([^)]*\))?)/s', $schemaContent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $columnType = $match[1];
            $columnName = $match[2];
            $modifiers = $match[3] ?? '';

            if (in_array($columnName, ['created_at', 'updated_at', 'deleted_at', 'id'])) {
                continue;
            }

            if (in_array($columnType, ['timestamps', 'softDeletes', 'softDeletesTz', 'remember_token'])) {
                continue;
            }

            $nullable = strpos($modifiers, '->nullable()') !== false;

            $properties[] = [
                'name' => $columnName,
                'type' => $this->mapMigrationTypeToPhpType($columnType),
                'nullable' => $nullable,
            ];
        }

        return $properties;
    }

    /**
     * Map database column type to PHP type.
     *
     * @param  string  $columnType
     * @return string
     */
    protected function mapDatabaseTypeToPhpType($columnType)
    {
        $type = strtolower($columnType);

        return match ($type) {
            'int', 'integer', 'bigint', 'smallint', 'tinyint' => 'int',
            'decimal', 'float', 'double', 'real' => 'float',
            'bool', 'boolean' => 'bool',
            'json', 'jsonb' => 'array',
            'date', 'datetime', 'timestamp' => '\DateTimeInterface',
            default => 'string',
        };
    }

    /**
     * Map migration column type to PHP type.
     *
     * @param  string  $migrationType
     * @return string
     */
    protected function mapMigrationTypeToPhpType($migrationType)
    {
        return match ($migrationType) {
            'bigInteger', 'integer', 'smallInteger', 'tinyInteger', 'unsignedBigInteger',
            'unsignedInteger', 'unsignedSmallInteger', 'unsignedTinyInteger', 'id', 'bigIncrements',
            'increments', 'tinyIncrements' => 'int',
            'decimal', 'float', 'double' => 'float',
            'boolean' => 'bool',
            'json', 'jsonb' => 'array',
            'date', 'datetime', 'timestamp', 'dateTime', 'dateTimeTz' => '\DateTimeInterface',
            default => 'string',
        };
    }

    /**
     * Build the DTO class with properties.
     *
     * @param  string  $name
     * @param  array  $properties
     * @return string
     */
    protected function buildDtoClass($name, $properties)
    {
        $stub = $this->files->get($this->getStub());

        $constructorParams = [];
        $docBlocks = [];

        foreach ($properties as $property) {
            $nullable = $property['nullable'] ? '?' : '';
            $type = $property['type'];
            $propName = $property['name'];

            $constructorParams[] = "        public {$nullable}{$type} \${$propName},";
            $docBlocks[] = " * @param {$nullable}{$type} \${$propName}";
        }

        $stub = str_replace(
            ['{{ constructorParams }}', '{{ docBlocks }}'],
            [implode("\n", $constructorParams), implode("\n", $docBlocks)],
            $stub
        );

        $this->replaceNamespace($stub, $name);
        $stub = $this->replaceClass($stub, $name);

        return $stub;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/dto.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Qualify the given model class base name.
     *
     * @param  string  $model
     * @return string
     */
    protected function qualifyModel($model)
    {
        $model = ltrim($model, '\\/');
        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return is_dir(app_path('Models'))
            ? $rootNamespace.'Models\\'.$model
            : $rootNamespace.$model;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Generate DTO from an existing model'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the DTO already exists'],
        ];
    }
}
