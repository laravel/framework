<?php

namespace Illuminate\Tests\Integration\Database;

use Orchestra\Testbench\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');

        if (! env('DB_CONNECTION')) {
            $app['config']->set('database.connections.testbench', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        }
    }

    protected function tearDown(): void
    {
        if (env('DB_CONNECTION')) {
            $this->artisan('db:wipe');
        }

        parent::tearDown();
    }
}
