<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\MariaDbGrammar;
use Illuminate\Database\Schema\MariaDbBuilder;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseMariaDbBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testCreateDatabase()
    {
        $connection = m::mock(Connection::class);
        $grammar = new MariaDbGrammar($connection);

        $connection->shouldReceive('getConfig')->once()->with('charset')->andReturn('utf8mb4');
        $connection->shouldReceive('getConfig')->once()->with('collation')->andReturn('utf8mb4_unicode_ci');
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'create database `my_temporary_database` default character set `utf8mb4` default collate `utf8mb4_unicode_ci`'
        )->andReturn(true);

        $builder = new MariaDbBuilder($connection);
        $builder->createDatabase('my_temporary_database');
    }

    public function testDropDatabaseIfExists()
    {
        $connection = m::mock(Connection::class);
        $grammar = new MariaDbGrammar($connection);

        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'drop database if exists `my_database_a`'
        )->andReturn(true);

        $builder = new MariaDbBuilder($connection);

        $builder->dropDatabaseIfExists('my_database_a');
    }
}
