<?php

namespace Illuminate\Tests\Integration\Database\Mysql;

use Orchestra\Testbench\TestCase;

class MysqlDatabaseTestCase extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'username' => 'root',
            'password' => '',
            'database' => 'forge',
            'prefix' => '',
        ]);
    }
}
