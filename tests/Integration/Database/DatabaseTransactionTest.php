<?php

declare(strict_types=1);

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DatabaseTransactionTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->unsignedInteger('id');
        });
    }

    public function testDatabaseTransactionCommit()
    {
        $connection = $this->getConnection();

        $connection->beginTransaction();

        $this->assertEquals(1, $connection->transactionLevel());

        $connection->table('users')->insert(['id' => 1]);
        $connection->commit();

        $this->assertEquals(0, $connection->transactionLevel());
        $this->assertDatabaseHas('users', ['id' => 1]);
    }

    public function testDatabaseTransactionRollback()
    {
        $connection = $this->getConnection();

        $connection->beginTransaction();

        $this->assertEquals(1, $connection->transactionLevel());

        $connection->table('users')->insert(['id' => 2]);
        $connection->rollBack();

        $this->assertEquals(0, $connection->transactionLevel());
        $this->assertDatabaseMissing('users', ['id' => 2]);
    }

    public function testDatabaseNestedTransactionCommmit()
    {
        $connection = $nestedConnection = $this->getConnection();

        $connection->beginTransaction();
        $connection->table('users')->insert(['id' => 1]);

        $this->assertEquals(1, $connection->transactionLevel());

        $nestedConnection->beginTransaction();

        $this->assertEquals(2, $connection->transactionLevel());

        $nestedConnection->table('users')->insert(['id' => 2]);
        $nestedConnection->commit();

        $this->assertEquals(1, $connection->transactionLevel());

        $connection->commit();

        $this->assertEquals(0, $connection->transactionLevel());
        $this->assertDatabaseHas('users', ['id' => 1]);
        $this->assertDatabaseHas('users', ['id' => 2]);
    }

    public function testDatabaseNestedTransactionRollback()
    {
        $connection = $nestedConnection = $this->getConnection();

        $connection->beginTransaction();
        $connection->table('users')->insert(['id' => 1]);

        $this->assertEquals(1, $connection->transactionLevel());

        $nestedConnection->beginTransaction();

        $this->assertEquals(2, $connection->transactionLevel());

        $nestedConnection->table('users')->insert(['id' => 2]);

        $nestedConnection->rollBack();

        $this->assertEquals(1, $connection->transactionLevel());

        $connection->commit();

        $this->assertEquals(0, $connection->transactionLevel());
        $this->assertDatabaseHas('users', ['id' => 1]);
        $this->assertDatabaseMissing('users', ['id' => 2]);
    }
}
