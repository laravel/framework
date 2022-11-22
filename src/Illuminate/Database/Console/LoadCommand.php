<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Events\SchemaLoaded;
use Illuminate\Database\SqlServerConnection;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schema:load')]
class LoadCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schema:load
                {--database= : The database connection to use}
                {--path= : The path to the schema dump file}
                {--drop-views : Drop all tables and views}
                {--drop-types : Drop all tables and types (Postgres only)}
                {--force : Force the operation to run when in production}
                {--prune : Delete schema file after loading}
        ';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'schema:load';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load the given database schema';

    /**
     * Create a new schema load command instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @return void
     */
    public function __construct(
        protected ConnectionResolverInterface $resolver,
        protected Dispatcher $dispatcher,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        $connection = $this->resolver->connection($database = $this->option('database'));

        if ($connection instanceof SqlServerConnection) {
            $this->components->warn('Incompatible database connection.');

            return;
        }

        if (is_null($path = $this->path($connection))) {
            $this->components->warn('No schema file found.');

            return;
        }

        $this->ensureCleanDatabase($connection);

        $this->components->info('Loading database schema.');

        $this->components->task($path, function () use ($connection, $path) {
            $connection->getSchemaState()->handleOutputUsing(function ($type, $buffer) {
                $this->output->write($buffer);
            })->load($path);
        });

        $this->newLine();

        $this->dispatcher->dispatch(new SchemaLoaded($connection, $path));

        $info = 'Database schema loaded';

        if ($this->option('prune')) {
            (new Filesystem)->delete($path);

            $info .= ' and pruned';
        }

        $this->components->info($info.' successfully.');
    }

    /**
     * Ensure database is clean to prevent errors when loading schema.
     *
     * @param  \Illuminate\Database\Connection  $connection
     */
    protected function ensureCleanDatabase(Connection $connection): void
    {
        if (! $connection->getSchemaBuilder()->hasTable(config('database.migrations'))) {
            return;
        }

        $this->newLine();

        $this->components->task('Dropping all tables', fn () => $this->callSilent('db:wipe', array_filter([
            '--database' => $this->option('database'),
            '--drop-views' => $this->option('drop-views'),
            '--drop-types' => $this->option('drop-types'),
            '--force' => true,
        ])) == 0);

        $this->newLine();
    }

    /**
     * Get the path to the stored schema for the given connection.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return string|null
     */
    protected function path(Connection $connection): ?string
    {
        return collect([
            $this->option('path'),
            $this->laravel->databasePath('schema/'.$connection->getName().'-schema.dump'),
            $this->laravel->databasePath('schema/'.$connection->getName().'-schema.sql'),
        ])->filter(fn ($file) => file_exists($file))
            ->first();
    }
}
