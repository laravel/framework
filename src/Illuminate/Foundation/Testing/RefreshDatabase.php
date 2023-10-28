<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\Traits\CanConfigureMigrationCommands;
use RuntimeException;

trait RefreshDatabase
{
    use CanConfigureMigrationCommands;

    private bool $shouldCheckTransactionLevel = false;

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function refreshDatabase()
    {
        $this->beforeRefreshingDatabase();

        $this->usingInMemoryDatabase()
                        ? $this->refreshInMemoryDatabase()
                        : $this->refreshTestDatabase();

        $this->afterRefreshingDatabase();
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
        $this->artisan('migrate', $this->migrateUsing());

        $this->app[Kernel::class]->setArtisan(null);
    }

    /**
     * The parameters that should be used when running "migrate".
     *
     * @return array
     */
    protected function migrateUsing()
    {
        return [
            '--seed' => $this->shouldSeed(),
            '--seeder' => $this->seeder(),
        ];
    }

    /**
     * Refresh a conventional test database.
     *
     * @return void
     */
    protected function refreshTestDatabase()
    {
        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh', $this->migrateFreshUsing());

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }

    /**
     * Begin a database transaction on the testing database.
     *
     * @return void
     */
    public function beginDatabaseTransaction()
    {
        $database = $this->app->make('db');

        $this->app->instance('db.transactions', $transactionsManager = new DatabaseTransactionsManager);

        foreach ($this->connectionsToTransact() as $name) {
            $connection = $database->connection($name);
            $connection->setTransactionManager($transactionsManager);
            $dispatcher = $connection->getEventDispatcher();

            $connection->unsetEventDispatcher();
            $connection->beginTransaction();
            $connection->setEventDispatcher($dispatcher);
        }

        $this->beforeApplicationDestroyed(function () use ($database) {
            foreach ($this->connectionsToTransact() as $name) {
                $connection = $database->connection($name);
                $isInvalidTransactionLevel = $this->shouldCheckTransactionLevel && $connection->transactionLevel() < 1;

                $dispatcher = $connection->getEventDispatcher();

                $connection->unsetEventDispatcher();
                $connection->rollBack();
                $connection->setEventDispatcher($dispatcher);
                $connection->disconnect();

                if ($isInvalidTransactionLevel) {
                    throw new RuntimeException('Transaction level mismatch detected: The number of transaction starts and ends do not match.');
                }
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
     * Checks if the number of transaction starts and ends match at the end of a test.
     *
     * @return void
     */
    protected function withCheckTransactionLevel()
    {
        $this->shouldCheckTransactionLevel = true;
    }

    /**
     * Skips the check of matching transaction start and end counts at the end of a test.
     */
    protected function withoutCheckTransactionLevel()
    {
        $this->shouldCheckTransactionLevel = false;
    }

    /**
     * Perform any work that should take place before the database has started refreshing.
     *
     * @return void
     */
    protected function beforeRefreshingDatabase()
    {
        // ...
    }

    /**
     * Perform any work that should take place once the database has finished refreshing.
     *
     * @return void
     */
    protected function afterRefreshingDatabase()
    {
        // ...
    }
}
