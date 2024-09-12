<?php

namespace Illuminate\Tests\Integration\Database\MariaDb;

use PDO;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

abstract class MariaDbTestCase extends DatabaseTestCase
{
    protected function defineDatabaseMigrations()
    {
        if ($this->driver !== 'mariadb') {
            $this->markTestSkipped('Test requires a MariaDB connection.');
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.connections.mariadb.options', [
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
        ]);
    }
}
