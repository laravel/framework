<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel;

trait RefreshDatabase
{
    use DatabaseTransactions;

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
        return config('database.connections')[
            config('database.default')
        ]['database'] == ':memory:';
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
            $this->artisan('migrate:fresh');

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }
}
