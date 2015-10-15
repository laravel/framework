<?php

namespace Illuminate\Foundation\Testing;

trait DatabaseMigrations
{
    /**
     * @before
     */
    public function runDatabaseMigrations()
    {
        $this->artisan('migrate');
    }

    /**
     * @after
     */
    public function runDatabaseMigrateRefresh()
    {
        $this->artisan('migrate:refresh');
    }
}
