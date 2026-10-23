<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'migrate:status')]
class StatusCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:status
                    {--database= : The database connection to use}
                    {--pending= : Only list pending migrations}
                    {--path=* : The path(s) to the migrations files to use}
                    {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                    {--json : Output the migration status as JSON}';

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

    #[\Override]
    protected function configureDefaults(): void
    {
        // Defaults to false (not null) so that handle() can distinguish "not passed
        // at all" from "passed with no value", which can't be expressed as a literal
        // boolean default in the signature above.
        $this->getDefinition()->getOption('pending')->setDefault(false);
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

                return self::FAILURE;
            }

            $ran = $this->migrator->getRepository()->getRan();

            $batches = $this->migrator->getRepository()->getMigrationBatches();

            if ($this->option('json')) {
                return $this->displayJson($ran, $batches);
            }

            $migrations = $this->getStatusFor($ran, $batches)
                ->when($this->option('pending') !== false, fn ($collection) => $collection->filter(function ($migration) {
                    return (new Stringable($migration[1]))->contains('Pending');
                }));

            if (count($migrations) > 0) {
                $this->newLine();

                $this->components->twoColumnDetail('<fg=gray>Migration name</>', '<fg=gray>Batch / Status</>');

                $migrations
                    ->each(
                        fn ($migration) => $this->components->twoColumnDetail($migration[0], $migration[1])
                    );

                $this->newLine();
            } elseif ($this->option('pending') !== false) {
                $this->components->info('No pending migrations');
            } else {
                $this->components->info('No migrations found');
            }

            if ($this->option('pending') && $migrations->some(fn ($m) => (new Stringable($m[1]))->contains('Pending'))) {
                return $this->option('pending');
            }
        });
    }

    /**
     * Display the migration status as JSON.
     *
     * @param  array  $ran
     * @param  array  $batches
     * @return int|null
     */
    protected function displayJson(array $ran, array $batches)
    {
        $migrations = $this->getMigrationStatuses($ran, $batches)
            ->when($this->option('pending') !== false, fn ($collection) => $collection->where('status', 'pending'));

        $this->output->writeln($migrations->values()->toJson());

        if ($this->option('pending') && $migrations->isNotEmpty()) {
            return $this->option('pending');
        }
    }

    /**
     * Get the status for the given run migrations.
     *
     * @param  array  $ran
     * @param  array  $batches
     * @return \Illuminate\Support\Collection
     */
    protected function getStatusFor(array $ran, array $batches)
    {
        return $this->getMigrationStatuses($ran, $batches)->map(fn ($migration) => [
            $migration['migration'],
            match ($migration['status']) {
                'ran' => '['.$migration['batch'].'] <fg=green;options=bold>Ran</>',
                'skipped' => '<fg=gray;options=bold>Skipped</>',
                'pending' => '<fg=yellow;options=bold>Pending</>',
            },
        ]);
    }

    /**
     * Get the name, batch, and status of each migration.
     *
     * @param  array  $ran
     * @param  array  $batches
     * @return \Illuminate\Support\Collection<string, array{migration: string, batch: int|null, status: 'ran'|'skipped'|'pending'}>
     */
    protected function getMigrationStatuses(array $ran, array $batches)
    {
        $hasPending = false;

        return (new Collection($this->getAllMigrationFiles()))
            ->map(function ($migration) use ($ran, $batches, &$hasPending) {
                $migrationName = $this->migrator->getMigrationName($migration);

                if (in_array($migrationName, $ran)) {
                    $status = 'ran';
                } elseif (! $hasPending && $this->migrator->shouldSkip($migration)) {
                    $status = 'skipped';
                } else {
                    $hasPending = true;

                    $status = 'pending';
                }

                return [
                    'migration' => $migrationName,
                    'batch' => $status === 'ran' ? (int) $batches[$migrationName] : null,
                    'status' => $status,
                ];
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
}
