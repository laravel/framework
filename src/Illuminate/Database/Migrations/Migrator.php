<?php

namespace Illuminate\Database\Migrations;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

class Migrator
{
    /**
     * The migration repository implementation.
     *
     * @var \Illuminate\Database\Migrations\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The name of the default connection.
     *
     * @var string
     */
    protected $connection;

    /**
     * The notes for the current operation.
     *
     * @var array
     */
    protected $notes = [];

    /**
     * The paths to all of the migration files.
     *
     * @var array
     */
    protected $paths = [];

    /**
     * Create a new migrator instance.
     *
     * @param  \Illuminate\Database\Migrations\MigrationRepositoryInterface  $repository
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(MigrationRepositoryInterface $repository,
                                Resolver $resolver,
                                Filesystem $files)
    {
        $this->files = $files;
        $this->resolver = $resolver;
        $this->repository = $repository;
    }

    /**
     * Run the outstanding migrations at a given path.
     *
     * @param  array|string  $paths
     * @param  array  $options
     * @return array
     */
    public function run($paths = [], array $options = [])
    {
        $this->notes = [];

        $files = $this->getMigrationFiles($paths);

        // Once we grab all of the migration files for the path, we will compare them
        // against the migrations that have already been run for this package then
        // run each of the outstanding migrations against a database connection.
        $ran = $this->repository->getRan();

        $migrations = Collection::make($files)
                        ->reject(function ($file) use ($ran) {
                            return in_array($this->getMigrationName($file), $ran);
                        })->values()->all();

        $this->requireFiles($migrations);

        $this->runMigrationList($migrations, $options);

        return $migrations;
    }

    /**
     * Run an array of migrations.
     *
     * @param  array  $migrations
     * @param  array  $options
     * @return void
     */
    public function runMigrationList($migrations, array $options = [])
    {
        // First we will just make sure that there are any migrations to run. If there
        // aren't, we will just make a note of it to the developer so they're aware
        // that all of the migrations have been run against this database system.
        if (count($migrations) == 0) {
            $this->note('<info>Nothing to migrate.</info>');

            return;
        }

        $batch = $this->repository->getNextBatchNumber();

        $pretend = Arr::get($options, 'pretend', false);

        $step = Arr::get($options, 'step', false);

        // Once we have the array of migrations, we will spin through them and run the
        // migrations "up" so the changes are made to the databases. We'll then log
        // that the migration was run so we don't repeat it next time we execute.
        foreach ($migrations as $file) {
            $this->runUp($file, $batch, $pretend);

            // If we are stepping through the migrations, then we will increment the
            // batch value for each individual migration that is run. That way we
            // can run "artisan migrate:rollback" and undo them one at a time.
            if ($step) {
                $batch++;
            }
        }
    }

    /**
     * Run "up" a migration instance.
     *
     * @param  string  $file
     * @param  int     $batch
     * @param  bool    $pretend
     * @return void
     */
    protected function runUp($file, $batch, $pretend)
    {
        $file = $this->getMigrationName($file);

        // First we will resolve a "real" instance of the migration class from this
        // migration file name. Once we have the instances we can run the actual
        // command such as "up" or "down", or we can just simulate the action.
        $migration = $this->resolve($file);

        if ($pretend) {
            return $this->pretendToRun($migration, 'up');
        }

        $migration->up();

        // Once we have run a migrations class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a migration
        // in the application. A migration repository keeps the migrate order.
        $this->repository->log($file, $batch);

        $this->note("<info>Migrated:</info> {$file}");
    }

    /**
     * Rollback the last migration operation.
     *
     * @param  array|string $paths
     * @param  array  $options
     * @return array
     */
    public function rollback($paths = [], array $options = [])
    {
        $this->notes = [];

        $rolledBack = [];

        // We want to pull in the last batch of migrations that ran on the previous
        // migration operation. We'll then reverse those migrations and run each
        // of them "down" to reverse the last migration "operation" which ran.
        if (($steps = Arr::get($options, 'step', 0)) > 0) {
            $migrations = $this->repository->getMigrations($steps);
        } else {
            $migrations = $this->repository->getLast();
        }

        $count = count($migrations);

        $files = $this->getMigrationFiles($paths);

        if ($count === 0) {
            $this->note('<info>Nothing to rollback.</info>');
        } else {
            // Next we will run through all of the migrations and call the "down" method
            // which will reverse each migration in order. This getLast method on the
            // repository already returns these migration's names in reverse order.
            $this->requireFiles($files);

            foreach ($migrations as $migration) {
                $rolledBack[] = $files[$migration->migration];

                $this->runDown(
                    $files[$migration->migration],
                    (object) $migration, Arr::get($options, 'pretend', false)
                );
            }
        }

        return $rolledBack;
    }

    /**
     * Rolls all of the currently applied migrations back.
     *
     * @param  array|string $paths
     * @param  bool  $pretend
     * @return array
     */
    public function reset($paths = [], $pretend = false)
    {
        $this->notes = [];

        $rolledBack = [];

        $files = $this->getMigrationFiles($paths);

        // Next, we will reverse the migration list so we can run them back in the
        // correct order for resetting this database. This will allow us to get
        // the database back into its "empty" state ready for the migrations.
        $migrations = array_reverse($this->repository->getRan());

        $count = count($migrations);

        if ($count === 0) {
            $this->note('<info>Nothing to rollback.</info>');
        } else {
            $this->requireFiles($files);

            // Next we will run through all of the migrations and call the "down" method
            // which will reverse each migration in order. This will get the database
            // back to its original "empty" state and will be ready for migrations.
            foreach ($migrations as $migration) {
                $rolledBack[] = $files[$migration];

                $this->runDown($files[$migration], (object) ['migration' => $migration], $pretend);
            }
        }

        return $rolledBack;
    }

    /**
     * Run "down" a migration instance.
     *
     * @param  string  $file
     * @param  object  $migration
     * @param  bool    $pretend
     * @return void
     */
    protected function runDown($file, $migration, $pretend)
    {
        $file = $this->getMigrationName($file);

        // First we will get the file name of the migration so we can resolve out an
        // instance of the migration. Once we get an instance we can either run a
        // pretend execution of the migration or we can run the real migration.
        $instance = $this->resolve($file);

        if ($pretend) {
            return $this->pretendToRun($instance, 'down');
        }

        $instance->down();

        // Once we have successfully run the migration "down" we will remove it from
        // the migration repository so it will be considered to have not been run
        // by the application then will be able to fire by any later operation.
        $this->repository->delete($migration);

        $this->note("<info>Rolled back:</info> {$file}");
    }

    /**
     * Get all of the migration files in a given path.
     *
     * @param  string|array  $paths
     * @return array
     */
    public function getMigrationFiles($paths)
    {
        return Collection::make($paths)->flatMap(function ($path) {
            return $this->files->glob($path.'/*_*.php');
        })->filter()->sortBy(function ($file) {
            return $this->getMigrationName($file);
        })->values()->keyBy(function ($file) {
            return $this->getMigrationName($file);
        })->all();
    }

    /**
     * Require in all the migration files in a given path.
     *
     * @param  array   $files
     * @return void
     */
    public function requireFiles(array $files)
    {
        foreach ($files as $file) {
            $this->files->requireOnce($file);
        }
    }

    /**
     * Pretend to run the migrations.
     *
     * @param  object  $migration
     * @param  string  $method
     * @return void
     */
    protected function pretendToRun($migration, $method)
    {
        foreach ($this->getQueries($migration, $method) as $query) {
            $name = get_class($migration);

            $this->note("<info>{$name}:</info> {$query['query']}");
        }
    }

    /**
     * Get all of the queries that would be run for a migration.
     *
     * @param  object  $migration
     * @param  string  $method
     * @return array
     */
    protected function getQueries($migration, $method)
    {
        $connection = $migration->getConnection();

        // Now that we have the connections we can resolve it and pretend to run the
        // queries against the database returning the array of raw SQL statements
        // that would get fired against the database system for this migration.
        $db = $this->resolveConnection($connection);

        return $db->pretend(function () use ($migration, $method) {
            $migration->$method();
        });
    }

    /**
     * Resolve a migration instance from a file.
     *
     * @param  string  $file
     * @return object
     */
    public function resolve($file)
    {
        $class = Str::studly(implode('_', array_slice(explode('_', $file), 4)));

        return new $class;
    }

    /**
     * Get the name of the migration.
     *
     * @param  string  $path
     * @return string
     */
    public function getMigrationName($path)
    {
        return str_replace('.php', '', basename($path));
    }

    /**
     * Raise a note event for the migrator.
     *
     * @param  string  $message
     * @return void
     */
    protected function note($message)
    {
        $this->notes[] = $message;
    }

    /**
     * Get the notes for the last operation.
     *
     * @return array
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Resolve the database connection instance.
     *
     * @param  string  $connection
     * @return \Illuminate\Database\Connection
     */
    public function resolveConnection($connection)
    {
        return $this->resolver->connection($connection);
    }

    /**
     * Register a custom migration path.
     *
     * These path will not automatically be applied.
     *
     * @param  string  $path
     * @return void
     */
    public function path($path)
    {
        $this->paths[] = $path;

        $this->paths = array_unique($this->paths);
    }

    /**
     * Get all of the custom migration paths.
     *
     * @return array
     */
    public function paths()
    {
        return $this->paths;
    }

    /**
     * Set the default connection name.
     *
     * @param  string  $name
     * @return void
     */
    public function setConnection($name)
    {
        if (! is_null($name)) {
            $this->resolver->setDefaultConnection($name);
        }

        $this->repository->setSource($name);

        $this->connection = $name;
    }

    /**
     * Get the migration repository instance.
     *
     * @return \Illuminate\Database\Migrations\MigrationRepositoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        return $this->repository->repositoryExists();
    }

    /**
     * Get the file system instance.
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }
}
