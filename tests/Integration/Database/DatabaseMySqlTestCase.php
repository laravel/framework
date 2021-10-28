<?php

namespace Illuminate\Tests\Integration\Database;

use Orchestra\Testbench\TestCase;

abstract class DatabaseMySqlTestCase extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');
        $app['config']->set('database.default', 'mysql');
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (! isset($_SERVER['CI'])) {
            $this->markTestSkipped('This test is only executed on CI.');
        }
    }
}
