<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel;

trait RefreshDatabase
{
    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function refreshDatabase()
    {
        $this->usingInMemoryDatabase()
                        ? $this->refreshInMemoryDatabase()
                        : $this->refreshTestDatabase();
    }

    /**
     * Determine if an in-memory database is being used.
     *
     * @return bool
     */
    protected function usingInMemoryDatabase()
    {
        $default = config('database.default');

        return config("database.connections.$default.database") === ':memory:';
    }

    /**
     * Refresh the in-memory database.
     *
     * @return void
     */
    protected function refreshInMemoryDatabase()
    {
        $this->artisan('migrate');

        $this->app[Kernel::class]->setArtisan(null);
    }

    /**
     * Refresh a conventional test database.
     *
     * @return void
     */
    protected function refreshTestDatabase()
    {
        if (! RefreshDatabaseState::$migrated) {
            // Fall back to "migrate" in case the test database was deleted
            $command = $this->shouldRefreshTestDatabase() ? 'migrate:fresh' : 'migrate';

            $this->artisan($command, $this->shouldDropViews() ? [
                '--drop-views' => true,
            ] : []);

            $this->app[Kernel::class]->setArtisan(null);

            $this->updateCachedMigrationHash();

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }

    /**
     * Determine if the database should be refreshed.
     *
     * @return bool
     */
    protected function shouldRefreshTestDatabase()
    {
        return $this->getCachedMigrationHash() !== $this->getMigrationHash();
    }

    /**
     * Calculate a hash based on the contents of each migration file.
     *
     * @return string
     */
    protected function getMigrationHash()
    {
        $migrationPath = $this->app->databasePath().DIRECTORY_SEPARATOR.'migrations';

        $migrationFiles = collect($this->app['migrator']->getMigrationFiles($migrationPath));

        return $migrationFiles->reduce(function ($hash, $file) {
            return md5($hash.file_get_contents($file));
        });
    }

    /**
     * Get the path to the migration cache file.
     *
     * @return string
     */
    protected function getCachedMigrationPath()
    {
        return storage_path('framework/testing/migrationhash');
    }

    /**
     * Get the current cached migration hash value.
     *
     * @return string
     */
    protected function getCachedMigrationHash()
    {
        $path = $this->getCachedMigrationPath();

        if (file_exists($path)) {
            return file_get_contents($path);
        }
    }

    /**
     * Update the cached migration hash value with the current migration hash.
     *
     * @return void
     */
    protected function updateCachedMigrationHash()
    {
        file_put_contents($this->getCachedMigrationPath(), $this->getMigrationHash());
    }

    /**
     * Begin a database transaction on the testing database.
     *
     * @return void
     */
    public function beginDatabaseTransaction()
    {
        $database = $this->app->make('db');

        foreach ($this->connectionsToTransact() as $name) {
            $connection = $database->connection($name);
            $dispatcher = $connection->getEventDispatcher();

            $connection->unsetEventDispatcher();
            $connection->beginTransaction();
            $connection->setEventDispatcher($dispatcher);
        }

        $this->beforeApplicationDestroyed(function () use ($database) {
            foreach ($this->connectionsToTransact() as $name) {
                $connection = $database->connection($name);
                $dispatcher = $connection->getEventDispatcher();

                $connection->unsetEventDispatcher();
                $connection->rollback();
                $connection->setEventDispatcher($dispatcher);
                $connection->disconnect();
            }
        });
    }

    /**
     * The database connections that should have transactions.
     *
     * @return array
     */
    protected function connectionsToTransact()
    {
        return property_exists($this, 'connectionsToTransact')
                            ? $this->connectionsToTransact : [null];
    }

    /**
     * Determine if views should be dropped when refreshing the database.
     *
     * @return bool
     */
    protected function shouldDropViews()
    {
        return property_exists($this, 'dropViews')
                            ? $this->dropViews : false;
    }
}
