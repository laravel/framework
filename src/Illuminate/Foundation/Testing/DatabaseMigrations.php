<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel;

trait DatabaseMigrations
{
    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function setUpDatabaseMigrations()
    {
        $this->artisan('migrate:fresh');

        $this->app[Kernel::class]->setArtisan(null);

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');

            RefreshDatabaseState::$migrated = false;
        });
    }

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @deprecated in favor of setUpDatabaseMigrations()
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->setUpDatabaseMigrations();
    }
}
