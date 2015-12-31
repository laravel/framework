<?php

namespace Illuminate\Foundation\Testing;

trait DatabaseMigrations
{
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
