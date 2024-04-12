<?php

namespace Illuminate\Tests\Integration\Database\MariaDb;

use Illuminate\Tests\Integration\Database\DatabaseTestCase;

abstract class MariaDbTestCase extends DatabaseTestCase
{
    protected function defineDatabaseMigrations()
    {
        if ($this->driver !== 'mariadb') {
            $this->markTestSkipped('Test requires a MariaDB connection.');
        }
    }
}
