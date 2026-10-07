<?php

namespace Illuminate\Tests\Database;

use JMac\Testing\Double;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as MySqlGrammarSchema;
use Illuminate\Database\Schema\MySqlBuilder;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseMySqlBuilderTest extends TestCase
{
    public function testCreateDatabase(): void
    {
        $connection = Double::for(Connection::class);
        $grammar = new MySqlGrammarSchema($connection);

        $connection->expects('getConfig')->with('charset')->returns('utf8mb4');
        $connection->expects('getConfig')->with('collation')->returns('utf8mb4_unicode_ci');
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('statement')->with('create database `my_temporary_database` default character set `utf8mb4` default collate `utf8mb4_unicode_ci`')->returns(true);

        $builder = new MySqlBuilder($connection);
        $builder->createDatabase('my_temporary_database');
    }

    public function testDropDatabaseIfExists()
    {
        $connection = Double::for(Connection::class);
        $grammar = new MySqlGrammarSchema($connection);

        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('statement')->with('drop database if exists `my_database_a`')->returns(true);

        $builder = new MySqlBuilder($connection);

        $builder->dropDatabaseIfExists('my_database_a');
    }

    public function testDeleteWithJoinCompilesOrderByAndLimit(): void
    {
        $connection = Double::for(Connection::class);
        $processor = new Processor;
        $grammar = new MySqlGrammar($connection);

        $connection->expects('getTablePrefix')->times(5)->returns('');

        $builder = new Builder($connection, $grammar, $processor);

        $builder
            ->from('users')
            ->join('contacts', 'users.id', '=', 'contacts.id')
            ->where('email', '=', 'foo')
            ->orderBy('users.id')
            ->limit(5);

        $sql = $grammar->compileDelete($builder);

        $this->assertStringContainsString('order by `users`.`id` asc', $sql);
        $this->assertStringContainsString('limit 5', $sql);
    }
}
