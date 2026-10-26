<?php

namespace Illuminate\Foundation\Testing;

trait LazilyRefreshDatabase
{
    use RefreshDatabase {
        refreshDatabase as baseRefreshDatabase;
        migrateDatabases as baseMigrateDatabases;
    }

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function refreshDatabase()
    {
        $database = $this->app->make('db');

        $callback = function () {
            if (RefreshDatabaseState::$lazilyRefreshed) {
                return;
            }

            RefreshDatabaseState::$lazilyRefreshed = true;

            if (property_exists($this, 'mockConsoleOutput')) {
                $shouldMockOutput = $this->mockConsoleOutput;

                $this->mockConsoleOutput = false;
            }

            $this->baseRefreshDatabase();

            if (property_exists($this, 'mockConsoleOutput')) {
                $this->mockConsoleOutput = $shouldMockOutput;
            }
        };

        foreach ($this->connectionsToTransact() as $connection) {
            $database->connection($connection)->beforeStartingTransaction($callback);
            $database->connection($connection)->beforeExecuting($callback);
        }

        $this->beforeApplicationDestroyed(function () {
            RefreshDatabaseState::$lazilyRefreshed = false;
        });
    }

    /**
     * Migrate the database without dispatching query events to the running test.
     *
     * @return void
     */
    protected function migrateDatabases()
    {
        $database = $this->app->make('db');

        $dispatchers = [];

        foreach ($this->connectionsToTransact() as $name) {
            $connection = $database->connection($name);

            $dispatchers[$name] = $connection->getEventDispatcher();

            $connection->unsetEventDispatcher();
        }

        try {
            $this->baseMigrateDatabases();
        } finally {
            foreach ($dispatchers as $name => $dispatcher) {
                if ($dispatcher) {
                    $database->connection($name)->setEventDispatcher($dispatcher);
                }
            }
        }
    }
}
