<?php

namespace Illuminate\Tests\Database;

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
        $connection = Mockery::mock(Connection::class);
        $grammar = new MySqlGrammarSchema($connection);

        $connection->shouldReceive('getConfig')->once()->with('charset')->andReturn('utf8mb4');
        $connection->shouldReceive('getConfig')->once()->with('collation')->andReturn('utf8mb4_unicode_ci');
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'create database `my_temporary_database` default character set `utf8mb4` default collate `utf8mb4_unicode_ci`'
        )->andReturn(true);

        $builder = new MySqlBuilder($connection);
        $builder->createDatabase('my_temporary_database');
    }

    public function testDropDatabaseIfExists()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = new MySqlGrammarSchema($connection);

        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'drop database if exists `my_database_a`'
        )->andReturn(true);

        $builder = new MySqlBuilder($connection);

        $builder->dropDatabaseIfExists('my_database_a');
    }

    public function testDeleteWithJoinCompilesOrderByAndLimit(): void
    {
        $connection = Mockery::mock(Connection::class);
        $processor = Mockery::mock(Processor::class);
        $grammar = new MySqlGrammar($connection);

        $connection->shouldReceive('getDatabaseName')->andReturn('database');
        $connection->shouldReceive('getTablePrefix')->andReturn('');

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
