<?php

namespace Illuminate\Tests\Integration\Database\MySql;

use Illuminate\Tests\Integration\Database\DatabaseTestCase;

abstract class MySqlTestCase extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->driver !== 'mysql') {
            $this->markTestSkipped('Test requires a MySQL connection.');
        }
    }
}
