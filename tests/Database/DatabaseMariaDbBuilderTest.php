<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\MariaDbGrammar;
use Illuminate\Database\Schema\MariaDbBuilder;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseMariaDbBuilderTest extends TestCase
{
    public function testCreateDatabase()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = new MariaDbGrammar($connection);

        $connection->expects('getConfig')->with('charset')->andReturn('utf8mb4');
        $connection->expects('getConfig')->with('collation')->andReturn('utf8mb4_unicode_ci');
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('statement')->with(
            'create database `my_temporary_database` default character set `utf8mb4` default collate `utf8mb4_unicode_ci`'
        )->andReturn(true);

        $builder = new MariaDbBuilder($connection);
        $builder->createDatabase('my_temporary_database');
    }

    public function testDropDatabaseIfExists()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = new MariaDbGrammar($connection);

        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('statement')->with(
            'drop database if exists `my_database_a`'
        )->andReturn(true);

        $builder = new MariaDbBuilder($connection);

        $builder->dropDatabaseIfExists('my_database_a');
    }
}
