<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Events\SchemaDumped;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;

class DumpCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'schema:dump';

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
    }

    /**
     * Create a schema state instance for the given connection.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return mixed
     */
    protected function schemaState(Connection $connection)
    {
        return $connection->getSchemaState(new Filesystem)
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
        return tap(database_path('migrations/schema/'.$connection->getName().'-schema.sql'), function ($path) {
            (new Filesystem)->ensureDirectoryExists(dirname($path));
        });
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
        ];
    }
}
