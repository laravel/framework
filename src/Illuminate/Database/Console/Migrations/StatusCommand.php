<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Database\Migrations\MigrationStatus;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'migrate:status')]
class StatusCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'migrate:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each migration';

    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new migration rollback command instance.
     *
     * @param  \Illuminate\Database\Migrations\Migrator  $migrator
     */
    public function __construct(Migrator $migrator)
    {
        parent::__construct();

        $this->migrator = $migrator;
    }

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        return $this->migrator->usingConnection($this->option('database'), function () {
            if (! $this->migrator->repositoryExists()) {
                $this->components->error('Migration table not found.');

                return 1;
            }

            $statuses = collect($this->option('status'))
                ->map(fn (string $status): string => ucfirst(strtolower($status)))
                ->filter(fn (string $status): bool => collect(MigrationStatus::cases())->map->value->contains($status))
                ->mapInto(MigrationStatus::class)
                ->when($this->option('pending') !== false, fn ($statuses) => $statuses->push(MigrationStatus::Pending, MigrationStatus::Skipped))
                ->unique();

            $migrations = (new Collection($this->getAllMigrationFiles()))
                ->map(function ($migration): array {
                    return [
                        'name' => $this->migrator->getMigrationName($migration),
                        'batch' => $this->migrator->getMigrationBatch($migration),
                        'status' => $this->migrator->getMigrationStatus($migration),
                    ];
                })
                ->when($statuses->isNotEmpty(), function ($migrations) use ($statuses) {
                    return $migrations->filter(fn ($migration) => $statuses->contains($migration['status']));
                });

            if (count($migrations) > 0) {
                $this->newLine();

                $this->components->twoColumnDetail('<fg=gray>Migration name</>', '<fg=gray>Batch / Status</>');

                $migrations->each(function ($migration) {
                    $this->components->twoColumnDetail(
                        $migration['name'],
                        ($migration['batch'] ? '['.$migration.'] ' : ' ').
                        match ($migration['status']) {
                            MigrationStatus::Ran => '<fg=green;options=bold>Ran</>',
                            MigrationStatus::Pending => '<fg=yellow;options=bold>Pending</>',
                            MigrationStatus::Skipped => '<fg=blue;options=bold>Skipped</>',
                        },
                    );
                });

                $this->newLine();
            } elseif (count($statuses) > 0) {
                $this->components->info('No '.$statuses->map(fn ($status) => strtolower($status->value))->join(', ', ' or ').' migrations');
            } else {
                $this->components->info('No migrations found');
            }

            return count($statuses) > 0 && count($migrations) > 0 ? self::FAILURE : self::SUCCESS;
        });
    }

    /**
     * Get an array of all of the migration files.
     *
     * @return array
     */
    protected function getAllMigrationFiles()
    {
        return $this->migrator->getMigrationFiles($this->getMigrationPaths());
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'],
            ['pending', null, InputOption::VALUE_OPTIONAL, 'Only list pending migrations (Deprecated)', false],
            ['status', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The statuses to filter migrations by (Ran, Pending, Skipped)'],
            ['path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The path(s) to the migrations files to use'],
            ['realpath', null, InputOption::VALUE_NONE, 'Indicate any provided migration file paths are pre-resolved absolute paths'],
        ];
    }
}
