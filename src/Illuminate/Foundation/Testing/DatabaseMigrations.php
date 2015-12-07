<?php

namespace Illuminate\Foundation\Testing;

trait DatabaseMigrations
{
    /**
     * @before
     */
    public function runDatabaseMigrations()
    {
        $this->afterApplicationCreated(function () {
            $this->artisan('migrate');
        });

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');
        });
    }
}
