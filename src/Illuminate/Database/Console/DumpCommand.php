<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Events\SchemaDumped;
use Illuminate\Filesystem\Filesystem;

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
                {--prune : Delete all existing migration files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump the given database schema';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ConnectionResolverInterface $connections, Dispatcher $dispatcher)
    {
        $this->schemaState(
            $connection = $connections->connection($database = $this->input->getOption('database'))
        )->dump($path = $this->path($connection));

        $dispatcher->dispatch(new SchemaDumped($connection, $path));

        $this->info('Database schema dumped successfully.');

        if ($this->option('prune')) {
            (new Filesystem)->deleteDirectory(
                database_path('migrations'), $preserve = false
            );

            $this->info('Migrations pruned successfully.');
        }
    }

    /**
     * Create a schema state instance for the given connection.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return mixed
     */
    protected function schemaState(Connection $connection)
    {
        return $connection->getSchemaState()
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
}
