<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Events\MigrationsPruned;
use Illuminate\Database\Events\SchemaDumped;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schema:dump')]
class DumpCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schema:dump
                {--database= : The database connection to use}
                {--path= : The path where the schema dump file should be stored}
                {--prune : Delete existing migration files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump the given database schema';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $connections
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @param  \Illuminate\Database\Migrations\Migrator  $migrator
     * @return void
     */
    public function handle(ConnectionResolverInterface $connections, Dispatcher $dispatcher, Migrator $migrator)
    {
        $connection = $connections->connection($this->input->getOption('database'));

        $this->schemaState($connection)->dump(
            $connection, $path = $this->path($connection)
        );

        $dispatcher->dispatch(new SchemaDumped($connection, $path));

        $info = 'Database schema dumped';

        if ($this->option('prune')) {
            $filesDeleted = $this->prune($migrator);

            $info .= ' and pruned';

            $dispatcher->dispatch(new MigrationsPruned($connection, $path, $filesDeleted));
        }

        $this->components->info($info.' successfully.');
    }

    /**
     * Create a schema state instance for the given connection.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return mixed
     */
    protected function schemaState(Connection $connection)
    {
        $migrations = Config::get('database.migrations', 'migrations');

        $migrationTable = is_array($migrations) ? ($migrations['table'] ?? 'migrations') : $migrations;

        return $connection->getSchemaState()
            ->withMigrationTable($migrationTable)
            ->handleOutputUsing(function ($type, $buffer) {
                $this->output->write($buffer);
            });
    }

    /**
     * Get the path that the dump should be written to.
     *
     * @param  \Illuminate\Database\Connection  $connection
     */
    protected function path(Connection $connection)
    {
        return tap($this->option('path') ?: database_path('schema/'.$connection->getName().'-schema.sql'), function ($path) {
            (new Filesystem)->ensureDirectoryExists(dirname($path));
        });
    }

    /**
     * Prune migration files.
     *
     * @param  Migrator  $migrator
     * @return array<int, string>
     */
    protected function prune(Migrator $migrator)
    {
        $migrations = $migrator->getMigrationFiles($this->laravel->databasePath('migrations'));
        if ($migrations === []) {
            return [];
        }

        $migrator->requireFiles($migrations);
        $filesDeleted = [];

        foreach ($migrations as $file) {
            $migration = $migrator->resolvePath($file);

            $shouldPruneMigration = $migration instanceof Migration
                ? $migration->shouldPrune()
                : true;

            if ($shouldPruneMigration) {
                $filesDeleted[] = $file;
            }
        }

        if ($filesDeleted !== []) {
            (new Filesystem)->delete($filesDeleted);
        }

        return $filesDeleted;
    }
}
