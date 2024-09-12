<?php

namespace Illuminate\Tests\Integration\Database\Sqlite;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class SqliteTestCase extends DatabaseTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        if ($this->driver !== 'sqlite') {
            $this->markTestSkipped('Test requires a Sqlite connection.');
        }

        tap($app['config'], function (Repository $config) {
            $config->set('database.default', 'conn1');
            $config->set('database.connections.conn1', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        });
    }
}
