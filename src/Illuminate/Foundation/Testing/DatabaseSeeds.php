<?php

namespace Illuminate\Foundation\Testing;

trait DatabaseSeeds
{
    /**
     * @before
     */
    public function runDatabaseSeeds()
    {
        $this->artisan('db:seed');
    }
}

