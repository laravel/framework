<?php

namespace Illuminate\Tests\Integration\Testing;

use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Testing\ParallelTestingServiceProvider;
use Orchestra\Testbench\TestCase;

class TestWithoutDatabaseParallelTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ParallelTestingServiceProvider::class];
    }

    /**
     * Define the test environment.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Given an application that does not use database connections at all
        $app['config']->set('database.default', null);

        // When we run parallel testing with `without-databases` option
        $_SERVER['LARAVEL_PARALLEL_TESTING'] = 1;
        $_SERVER['LARAVEL_PARALLEL_TESTING_WITHOUT_DATABASES'] = 1;
        $_SERVER['TEST_TOKEN'] = '1';

        $this->beforeApplicationDestroyed(function () {
            unset(
                $_SERVER['LARAVEL_PARALLEL_TESTING'],
                $_SERVER['LARAVEL_PARALLEL_TESTING_WITHOUT_DATABASES'],
                $_SERVER['TEST_TOKEN'],
            );
        });
    }

    public function testRunningParallelTestWithoutDatabaseShouldNotCrashOnDefaultConnection()
    {
        // We should not create a database connection to check if it's SQLite or not.
        ParallelTesting::callSetUpProcessCallbacks();
    }
}
