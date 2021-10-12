<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel;

trait DatabaseMigrations
{
    /**
     * Re-migrate the database before each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->artisan('migrate:fresh');

        $this->app[Kernel::class]->setArtisan(null);
    }
}
