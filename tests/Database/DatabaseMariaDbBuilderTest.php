<?php

namespace Illuminate\Tests\Database;

use JMac\Testing\Double;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\MariaDbGrammar;
use Illuminate\Database\Schema\MariaDbBuilder;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseMariaDbBuilderTest extends TestCase
{
    public function testCreateDatabase()
    {
        $connection = Double::for(Connection::class);
        $grammar = new MariaDbGrammar($connection);

        $connection->expects('getConfig')->with('charset')->returns('utf8mb4');
        $connection->expects('getConfig')->with('collation')->returns('utf8mb4_unicode_ci');
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('statement')->with('create database `my_temporary_database` default character set `utf8mb4` default collate `utf8mb4_unicode_ci`')->returns(true);

        $builder = new MariaDbBuilder($connection);
        $builder->createDatabase('my_temporary_database');
    }

    public function testDropDatabaseIfExists()
    {
        $connection = Double::for(Connection::class);
        $grammar = new MariaDbGrammar($connection);

        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('statement')->with('drop database if exists `my_database_a`')->returns(true);

        $builder = new MariaDbBuilder($connection);

        $builder->dropDatabaseIfExists('my_database_a');
    }
}
