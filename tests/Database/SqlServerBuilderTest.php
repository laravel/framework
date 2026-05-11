<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar;
use Illuminate\Database\Schema\SqlServerBuilder;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SqlServerBuilderTest extends TestCase
{
    public function testCreateDatabase()
    {
        $connection = m::mock(Connection::class);
        $grammar = new SqlServerGrammar($connection);

        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'create database "my_temporary_database_a"'
        )->andReturn(true);

        $builder = new SqlServerBuilder($connection);
        $builder->createDatabase('my_temporary_database_a');
    }

    public function testDropDatabaseIfExists()
    {
        $connection = m::mock(Connection::class);
        $grammar = new SqlServerGrammar($connection);

        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'drop database if exists "my_temporary_database_b"'
        )->andReturn(true);

        $builder = new SqlServerBuilder($connection);

        $builder->dropDatabaseIfExists('my_temporary_database_b');
    }

    public function testItUsesNativeJsonTypeForSqlServer2025Plus()
    {
        // Mockery is already aliased as 'm' at the top of the file.
        $connection = m::mock(Connection::class);
        $grammar = new SqlServerGrammar($connection);

        // CRITICAL: Mock the internal connection method to return the version supporting the feature.
        $connection->shouldReceive('getServerVersion')->once()->andReturn('2025.0.0');
        // Mock table prefix if needed by the Grammar methods you touch.
        $connection->shouldReceive('getTablePrefix')->andReturn('');

        $column = new \Illuminate\Support\Fluent(['name' => 'data', 'type' => 'json']);

        // Assert that the Grammar returns the native JSON type.
        $this->assertEquals('json', $grammar->typeJson($column));
    }

    public function testItFallsBackToNvarcharMaxForSqlServerPre2025()
    {
        $connection = m::mock(Connection::class);
        $grammar = new SqlServerGrammar($connection);

        // CRITICAL: Mock the internal connection method to return an older version.
        $connection->shouldReceive('getServerVersion')->once()->andReturn('2019.0.0');
        $connection->shouldReceive('getTablePrefix')->andReturn('');

        $column = new \Illuminate\Support\Fluent(['name' => 'data', 'type' => 'json']);

        // Assert that the Grammar falls back to the old nvarchar(max) type.
        $this->assertEquals('nvarchar(max)', $grammar->typeJson($column));
    }
}
