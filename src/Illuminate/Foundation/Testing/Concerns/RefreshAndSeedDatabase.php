<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabaseState;

trait RefreshAndSeedDatabase
{
    use RefreshDatabase {
        refreshInMemoryDatabase as baseRefreshInMemoryDatabase;
    }

    /**
     * Refresh the in-memory database.
     *
     * @return void
     */
    protected function refreshInMemoryDatabase()
    {
        $this->baseRefreshInMemoryDatabase();

        $this->artisan('db:seed', ['--class' => $this->databaseSeederClass()]);

        $this->app[Kernel::class]->setArtisan(null);
    }

    /**
     * Refresh a conventional test database.
     *
     * @return void
     */
    protected function refreshTestDatabase()
    {
        if (!RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh');

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        if (!RefreshDatabaseState::$seeded) {
            $this->artisan('db:seed', ['--class' => $this->databaseSeederClass()]);

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$seeded = true;
        }

        $this->beginDatabaseTransaction();
    }

    /**
     * Get the database seeder class to seed with.
     *
     * @return string
     */
    protected function databaseSeederClass()
    {
        return 'DatabaseSeeder';
    }
}
