<?php

namespace Illuminate\Foundation\Testing;

trait SeedDatabase
{
    /**
     * Seed the database.
     *
     * @param string $seederClass
     * @param string $connection
     * @return void
     */
    public function seed(string $seederClass = 'DatabaseSeeder', string $connection = '')
    {
        if ($seederClass == '') {
            $seederClass = 'DatabaseSeeder';
        }
        if ($connection == '') {
            $connection = config('database.default');
        }

        $this->artisan(sprintf('db:seed --class=%s --database=%s', $seederClass, $connection));
    }
}
