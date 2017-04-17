<?php

namespace Illuminate\Foundation\Testing;

trait DatabaseSeeds
{
    /**
     * @before
     */
    public function runDatabaseSeeder()
    {
        $this->artisan('db:seed');
    }
}
