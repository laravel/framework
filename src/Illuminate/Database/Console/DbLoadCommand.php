<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Events\DatabaseLoaded;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'db:load')]
class DbLoadCommand extends Command
{
    use ConfirmableTrait, Prohibitable;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'db:load {path}
                {--database= : The database connection to use}
                {--force : Force the operation to run when in production}
                {--drop : Drop the database before loading the dump}';

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
        if ($this->isProhibited() ||
            ! $this->confirmToProceed()) {
            return Command::FAILURE;
        }

        $connection = $connections->connection($database = $this->input->getOption('database'));

        $path = $this->argument('path');

        if ($this->input->getOption('drop')) {
            $this->call('db:wipe', array_filter([
                '--database' => $database,
                '--force' => true,
            ]));
        }

        $this->schemaState($connection)->load($path);

        $dispatcher->dispatch(new DatabaseLoaded($connection, $path));

        $info = 'Database loaded';

        $this->components->info($info.' successfully in database '.$database);
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
