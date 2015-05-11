<?php namespace Illuminate\Foundation\Testing;

trait Migrations
{
    /**
     * @before
     */
    public function runDatabaseMigrations()
    {
        $this->artisan('migrate');

        $this->beforeApplicationDestroyed(function() {
            $this->artisan('migrate:rollback');
        });
    }
}
