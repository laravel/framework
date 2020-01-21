<?php

namespace Illuminate\Tests\Integration\Database;

class DatabaseEmulatePreparesMySqlConnectionTest extends DatabaseMySqlConnectionTest
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        // Database configuration
        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'username' => 'root',
            'password' => '',
            'database' => 'forge',
            'prefix' => '',
            'options' => [
                \PDO::ATTR_EMULATE_PREPARES => true,
            ],
        ]);
    }
}
