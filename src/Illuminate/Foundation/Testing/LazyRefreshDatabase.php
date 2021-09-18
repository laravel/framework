<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Database\Events\QueryExecuting;

trait LazyRefreshDatabase
{
    use RefreshDatabase {
        refreshDatabase as standardRefreshDatabase;
    }

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function refreshDatabase()
    {
        $events = $this->app->make('events');

        $events->listen(QueryExecuting::class, function () {
            if (RefreshDatabaseState::$lazilyRefreshed) {
                return;
            }

            RefreshDatabaseState::$lazilyRefreshed = true;

            $this->usingInMemoryDatabase()
                ? $this->refreshInMemoryDatabase()
                : $this->refreshTestDatabase();
        });

        $this->beforeApplicationDestroyed(function () {
            RefreshDatabaseState::$lazilyRefreshed = false;
        });
    }
}
