<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\Traits\CanConfigureMigrationCommands;
use Illuminate\Support\Hooks\Hook;

trait DatabaseMigrations
{
    use CanConfigureMigrationCommands;

    /**
     * Register test case hook.
     *
     * @return \Illuminate\Support\Hooks\Hook
     */
    public function registerDatabaseMigrationsHook(): Hook
    {
        return new Hook('setUp', fn () => $this->runDatabaseMigrations(), 55);
    }

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->artisan('migrate:fresh', $this->migrateFreshUsing());

        $this->app[Kernel::class]->setArtisan(null);

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');

            RefreshDatabaseState::$migrated = false;
        });
    }
}
