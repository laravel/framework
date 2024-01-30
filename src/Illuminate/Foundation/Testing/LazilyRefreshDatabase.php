<?php

namespace Illuminate\Foundation\Testing;

trait LazilyRefreshDatabase
{
    use RefreshDatabase {
        refreshDatabase as baseRefreshDatabase;
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

            $shouldMockOutput = $this->mockConsoleOutput;

            $this->mockConsoleOutput = false;

            $this->baseRefreshDatabase();

            $this->mockConsoleOutput = $shouldMockOutput;
        };

        $database->beforeStartingTransaction($callback);
        $database->beforeExecuting($callback);

        $this->beforeApplicationDestroyed(function () {
            RefreshDatabaseState::$lazilyRefreshed = false;
        });
    }
}
