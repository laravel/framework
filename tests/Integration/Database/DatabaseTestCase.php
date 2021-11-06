<?php

namespace Illuminate\Tests\Integration\Database;

use Orchestra\Testbench\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        if (! env('DB_CONNECTION')) {
            $app['config']->set('database.default', 'testbench');
        }
    }

    protected function tearDown(): void
    {
        if ($this->app['config']->get('database.default') !== 'testbench') {
            $this->artisan('db:wipe', ['--drop-views' => true]);
        }

        parent::tearDown();
    }
}
