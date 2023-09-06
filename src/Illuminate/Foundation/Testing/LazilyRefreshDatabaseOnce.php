<?php

namespace Illuminate\Foundation\Testing;

trait LazilyRefreshDatabaseOnce
{
    use RefreshDatabase {
        refreshDatabase as baseRefreshDatabase;
    }

    /**
     * Refresh the database, but only if it hasn't been refreshed before in the current test run.
     *
     * @return void
     */
    public function refreshDatabase()
    {
        $database = $this->app->make('db');

        $database->beforeExecuting(function () {
            if (RefreshDatabaseState::$migrated) {
                return;
            }

            RefreshDatabaseState::$migrated = true;

            $this->baseRefreshDatabase();
        });
    }
}
