<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\MysqlGrammar;
use Illuminate\Database\Schema\MySqlBuilder;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseMySqlBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testCreateDatabaseIfNotExists()
    {
        $grammar = new MysqlGrammar();

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getConfig')->once()->once()->with('charset')->andReturn('utf8mb4');
        $connection->shouldReceive('getConfig')->once()->once()->with('collation')->andReturn('utf8mb4_unicode_ci');
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'CREATE DATABASE IF NOT EXISTS `my_temporary_database` CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;'
        )->andReturn(true);

        $builder = new MySqlBuilder($connection);
        $builder->createDatabaseIfNotExists('my_temporary_database');
    }

    public function testDropDatabaseIfExists()
    {
        $grammar = new MysqlGrammar();

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'DROP DATABASE IF EXISTS `my_database_a`;'
        )->andReturn(true);

        $builder = new MySqlBuilder($connection);

        $builder->dropDatabaseIfExists('my_database_a');
    }
}
