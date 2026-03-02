<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\DatabaseRefreshed;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Arr;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

#[AsCommand(name: 'migrate:fresh')]
class FreshCommand extends Command
{
    use ConfirmableTrait, Prohibitable;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'migrate:fresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all tables and re-run all migrations';

    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new fresh command instance.
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
     * @return int
     */
    public function handle()
    {
        if ($this->isProhibited() ||
            ! $this->confirmToProceed()) {
            return Command::FAILURE;
        }

        $database = $this->input->getOption('database');
        $snapshotPath = null;

        $this->migrator->usingConnection($database, function () use ($database, &$snapshotPath) {
            if ($this->migrator->repositoryExists()) {
                if ($this->shouldPreserveData()) {
                    $this->newLine();

                    $snapshotPath = $this->createDataSnapshot($database);
                }

                $this->newLine();

                $this->components->task('Dropping all tables', fn () => $this->callSilent('db:wipe', array_filter([
                    '--database' => $database,
                    '--drop-views' => $this->option('drop-views'),
                    '--drop-types' => $this->option('drop-types'),
                    '--force' => true,
                ])) == 0);
            }
        });

        $this->newLine();

        $migrateStatus = $this->call('migrate', array_filter([
            '--database' => $database,
            '--path' => $this->input->getOption('path'),
            '--realpath' => $this->input->getOption('realpath'),
            '--schema-path' => $this->input->getOption('schema-path'),
            '--force' => true,
            '--step' => $this->option('step'),
        ]));

        if ($migrateStatus !== Command::SUCCESS) {
            if ($snapshotPath) {
                $this->components->warn("The data snapshot is available at [{$snapshotPath}].");
            }

            return $migrateStatus;
        }

        if ($snapshotPath) {
            try {
                $this->newLine();

                $this->restoreDataSnapshot($database, $snapshotPath);
            } catch (Throwable $e) {
                $this->components->error("Unable to restore the data snapshot. The snapshot is still available at [{$snapshotPath}].");

                throw $e;
            }
        }

        if ($this->laravel->bound(Dispatcher::class)) {
            $this->laravel[Dispatcher::class]->dispatch(
                new DatabaseRefreshed($database, $this->needsSeeding())
            );
        }

        if ($this->needsSeeding()) {
            $this->runSeeder($database);
        }

        if ($snapshotPath && is_file($snapshotPath)) {
            @unlink($snapshotPath);
        }

        return Command::SUCCESS;
    }

    /**
     * Determine if the developer has requested data preservation.
     *
     * @return bool
     */
    protected function shouldPreserveData()
    {
        return $this->option('preserve-data');
    }

    /**
     * Create a temporary data snapshot file.
     *
     * @param  string|null  $database
     * @return string|null
     */
    protected function createDataSnapshot($database)
    {
        $snapshotPath = null;

        $this->components->task('Exporting table data', function () use ($database, &$snapshotPath) {
            $snapshot = $this->gatherDataSnapshot($database);

            if ($snapshot === []) {
                return true;
            }

            $snapshotPath = tempnam(sys_get_temp_dir(), 'migrate-fresh-');

            if ($snapshotPath === false) {
                throw new RuntimeException('Unable to create a temporary data snapshot file.');
            }

            if (file_put_contents($snapshotPath, serialize($snapshot), LOCK_EX) === false) {
                throw new RuntimeException('Unable to write the temporary data snapshot file.');
            }

            return true;
        });

        return $snapshotPath;
    }

    /**
     * Gather table data that can be safely restored after a fresh migration.
     *
     * @param  string|null  $database
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function gatherDataSnapshot($database)
    {
        $connection = $this->migrator->resolveConnection($database);

        $migrationTables = $this->migrationTableNames($connection);

        $tables = array_values(array_filter(
            $connection->getSchemaBuilder()->getTableListing(null, false),
            fn ($table) => ! in_array($table, $migrationTables, true)
        ));

        $snapshot = [];

        foreach ($tables as $table) {
            $columns = $this->getInsertableColumns($connection, $table);

            if ($columns === []) {
                continue;
            }

            $rows = array_map(
                fn ($row) => (array) $row,
                $connection->table($table)->useWritePdo()->get($columns)->all()
            );

            if ($rows !== []) {
                $snapshot[$table] = $rows;
            }
        }

        return $snapshot;
    }

    /**
     * Restore a previously captured data snapshot.
     *
     * @param  string|null  $database
     * @param  string  $snapshotPath
     * @return void
     */
    protected function restoreDataSnapshot($database, $snapshotPath)
    {
        $this->components->task('Restoring table data', function () use ($database, $snapshotPath) {
            if (! is_file($snapshotPath)) {
                throw new RuntimeException('Unable to read the temporary data snapshot file.');
            }

            $contents = file_get_contents($snapshotPath);

            if ($contents === false) {
                throw new RuntimeException('Unable to read the temporary data snapshot file.');
            }

            $snapshot = unserialize($contents, ['allowed_classes' => false]);

            if (! is_array($snapshot) || $snapshot === []) {
                return true;
            }

            $connection = $this->migrator->resolveConnection($database);
            $schema = $connection->getSchemaBuilder();

            $schema->withoutForeignKeyConstraints(function () use ($connection, $schema, $snapshot) {
                foreach ($snapshot as $table => $rows) {
                    if (! is_array($rows) || $rows === [] ||
                        ! $schema->hasTable($table)) {
                        continue;
                    }

                    $insertableColumns = $this->getInsertableColumns($connection, $table);

                    if ($insertableColumns === []) {
                        continue;
                    }

                    $preparedRows = array_values(array_filter(array_map(
                        fn ($row) => Arr::only((array) $row, $insertableColumns),
                        $rows
                    ), fn ($row) => $row !== []));

                    if ($preparedRows === []) {
                        continue;
                    }

                    $autoIncrementColumns = $this->getAutoIncrementColumns($connection, $table);

                    $this->insertSnapshotRows($connection, $table, $preparedRows, $autoIncrementColumns);

                    if ($connection->getDriverName() === 'pgsql' && $autoIncrementColumns !== []) {
                        $this->syncPostgresSequences($connection, $table, $autoIncrementColumns);
                    }
                }
            });

            return true;
        });
    }

    /**
     * Get table names that should not be restored.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return array<int, string>
     */
    protected function migrationTableNames(Connection $connection)
    {
        $table = $this->getMigrationTableName();

        return array_values(array_unique([
            $table,
            $connection->getTablePrefix().$table,
        ]));
    }

    /**
     * Resolve the migration repository table name.
     *
     * @return string
     */
    protected function getMigrationTableName()
    {
        $migrations = $this->laravel['config']->get('database.migrations', 'migrations');

        if (is_array($migrations)) {
            return $migrations['table'] ?? 'migrations';
        }

        return $migrations;
    }

    /**
     * Determine which table columns can be restored.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $table
     * @return array<int, string>
     */
    protected function getInsertableColumns(Connection $connection, $table)
    {
        return array_values(array_map(
            fn ($column) => $column['name'],
            array_filter(
                $connection->getSchemaBuilder()->getColumns($table),
                fn ($column) => is_null($column['generation'])
            )
        ));
    }

    /**
     * Determine auto-increment columns for the given table.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $table
     * @return array<int, string>
     */
    protected function getAutoIncrementColumns(Connection $connection, $table)
    {
        return array_values(array_map(
            fn ($column) => $column['name'],
            array_filter(
                $connection->getSchemaBuilder()->getColumns($table),
                fn ($column) => $column['auto_increment']
            )
        ));
    }

    /**
     * Insert a snapshot payload for the given table.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $table
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $autoIncrementColumns
     * @return void
     */
    protected function insertSnapshotRows(Connection $connection, $table, array $rows, array $autoIncrementColumns)
    {
        $usesIdentityInsert = $connection->getDriverName() === 'sqlsrv'
            && $this->rowsContainColumns($rows, $autoIncrementColumns);

        if (! $usesIdentityInsert) {
            foreach (array_chunk($rows, 1000) as $chunk) {
                $connection->table($table)->useWritePdo()->insert($chunk);
            }

            return;
        }

        $wrappedTable = $connection->getQueryGrammar()->wrapTable($table);

        $connection->statement("SET IDENTITY_INSERT {$wrappedTable} ON");

        try {
            foreach (array_chunk($rows, 1000) as $chunk) {
                $connection->table($table)->useWritePdo()->insert($chunk);
            }
        } finally {
            $connection->statement("SET IDENTITY_INSERT {$wrappedTable} OFF");
        }
    }

    /**
     * Determine if any of the rows include one of the given columns.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $columns
     * @return bool
     */
    protected function rowsContainColumns(array $rows, array $columns)
    {
        if ($columns === []) {
            return false;
        }

        foreach ($rows as $row) {
            if (array_intersect($columns, array_keys($row)) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sync PostgreSQL sequences after explicit inserts.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $table
     * @param  array<int, string>  $columns
     * @return void
     */
    protected function syncPostgresSequences(Connection $connection, $table, array $columns)
    {
        foreach ($columns as $column) {
            $sequence = $connection->scalar('select pg_get_serial_sequence(?, ?) as sequence', [$table, $column]);

            if (! is_string($sequence) || $sequence === '') {
                continue;
            }

            $max = $connection->table($table)->useWritePdo()->max($column);

            $connection->statement('select setval(?::regclass, ?, ?)', [
                $sequence,
                $max ?? 1,
                ! is_null($max),
            ]);
        }
    }

    /**
     * Determine if the developer has requested database seeding.
     *
     * @return bool
     */
    protected function needsSeeding()
    {
        return $this->option('seed') || $this->option('seeder');
    }

    /**
     * Run the database seeder command.
     *
     * @param  string  $database
     * @return void
     */
    protected function runSeeder($database)
    {
        $this->call('db:seed', array_filter([
            '--database' => $database,
            '--class' => $this->option('seeder') ?: 'Database\\Seeders\\DatabaseSeeder',
            '--force' => true,
        ]));
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
            ['drop-views', null, InputOption::VALUE_NONE, 'Drop all tables and views'],
            ['drop-types', null, InputOption::VALUE_NONE, 'Drop all tables and types (Postgres only)'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
            ['path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The path(s) to the migrations files to be executed'],
            ['realpath', null, InputOption::VALUE_NONE, 'Indicate any provided migration file paths are pre-resolved absolute paths'],
            ['schema-path', null, InputOption::VALUE_OPTIONAL, 'The path to a schema dump file'],
            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run'],
            ['seeder', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder'],
            ['step', null, InputOption::VALUE_NONE, 'Force the migrations to be run so they can be rolled back individually'],
            ['preserve-data', null, InputOption::VALUE_NONE, 'Export and restore table data across the fresh migration'],
        ];
    }
}
