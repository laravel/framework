<?php

namespace Illuminate\Foundation\Testing;

trait RefreshDatabaseOnce
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
        if (RefreshDatabaseState::$migrated) {
            return;
        }

        RefreshDatabaseState::$migrated = true;

        $this->baseRefreshDatabase();
    }
}
