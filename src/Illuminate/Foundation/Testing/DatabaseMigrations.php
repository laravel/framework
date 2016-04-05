<?php

namespace Illuminate\Foundation\Testing;

trait DatabaseMigrations
{
    /**
     * Run the database migrations for the application.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->artisan('migrate');

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');
        });
    }
}
