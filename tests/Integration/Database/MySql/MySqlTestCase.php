<?php

namespace Illuminate\Tests\Integration\Database\MySql;

use PDO;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

abstract class MySqlTestCase extends DatabaseTestCase
{
    protected function defineDatabaseMigrations()
    {
        if ($this->driver !== 'mysql') {
            $this->markTestSkipped('Test requires a MySQL connection.');
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.connections.mysql.options', [
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
        ]);
    }
}
