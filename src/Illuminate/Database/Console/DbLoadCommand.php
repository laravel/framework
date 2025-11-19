<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Events\DatabaseDumped;
use Illuminate\Database\Events\MigrationsPruned;
use Illuminate\Database\Events\SchemaDumped;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'db:load')]
class DbLoadCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'db:load
                {--database= : The database connection to use}
                {--path= : The path where the schema dump file is stored}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load the databse dump into the give database';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $connections
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @return void
     */
    public function handle(ConnectionResolverInterface $connections, Dispatcher $dispatcher)
    {
        $connection = $connections->connection($database = $this->input->getOption('database'));

        $path = $this->input->getOption('path') ?: $this->ask('Path to the schema dump file');
        $this->schemaState($connection)->load($path);

        $dispatcher->dispatch(new DatabaseDumped($connection, $path));

        $info = 'Database loaded';

        $this->components->info($info.' successfully in database ' . $database);
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
}
