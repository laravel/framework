<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar;
use Illuminate\Database\Schema\SqlServerBuilder;
use Mockery;
use PHPUnit\Framework\TestCase;

class SqlServerBuilderTest extends TestCase
{
    public function testCreateDatabase()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = new SqlServerGrammar($connection);

        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('statement')->with(
            'create database "my_temporary_database_a"'
        )->andReturn(true);

        $builder = new SqlServerBuilder($connection);
        $builder->createDatabase('my_temporary_database_a');
    }

    public function testDropDatabaseIfExists()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = new SqlServerGrammar($connection);

        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('statement')->with(
            'drop database if exists "my_temporary_database_b"'
        )->andReturn(true);

        $builder = new SqlServerBuilder($connection);

        $builder->dropDatabaseIfExists('my_temporary_database_b');
    }

    public function testAddingJsonOnSqlServer2025()
    {
        $connection = Mockery::mock(Connection::class);

        $grammar = new SqlServerGrammar($connection);

        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('getServerVersion')->andReturn('17.0.4075.5');
        $connection->expects('getTablePrefix')->andReturn('');

        $blueprint = new Blueprint($connection, 'users');

        $blueprint->json('data');

        $statements = $blueprint->toSql();

        $this->assertSame(
            'alter table "users" add "data" json not null',
            $statements[0]
        );
    }

    public function testAddingJsonOnSqlServerBefore2025()
    {
        $connection = Mockery::mock(Connection::class);

        $grammar = new SqlServerGrammar($connection);

        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('getServerVersion')->andReturn('16.0.4236.7');
        $connection->expects('getTablePrefix')->andReturn('');

        $blueprint = new Blueprint($connection, 'users');

        $blueprint->json('data');

        $statements = $blueprint->toSql();

        $this->assertSame(
            'alter table "users" add "data" nvarchar(max) not null',
            $statements[0]
        );
    }
}
