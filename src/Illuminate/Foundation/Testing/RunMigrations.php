<?php namespace Illuminate\Foundation\Testing;

trait RunMigrations
{
    /**
     * @before
     */
    public function runMigrations()
    {
        // run application migrations
        $this->artisan('migrate');

        // register the rollback action before destroying
        // the application
        $this->beforeApplicationDestroyed(function() {
            $this->rollbackMigrations();
        });
    }


    public function rollbackMigrations()
    {
        $this->artisan('migrate:rollback');
    }
}
