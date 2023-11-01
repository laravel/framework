<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\PostgresProcessor;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\PostgresBuilder;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabasePostgresSchemaBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testHasTable()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getDatabaseName')->andReturn('db');
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getConfig')->with('database')->andReturn('db');
        $connection->shouldReceive('getConfig')->with('schema')->andReturn('schema');
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $builder = new PostgresBuilder($connection);
        $grammar->shouldReceive('compileTableExists')->once()->andReturn('sql');
        $connection->shouldReceive('getTablePrefix')->once()->andReturn('prefix_');
        $connection->shouldReceive('selectFromWriteConnection')->once()->with('sql', ['db', 'public', 'prefix_table'])->andReturn(['prefix_table']);

        $this->assertTrue($builder->hasTable('table'));
    }

    public function testGetColumnListing()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(PostgresGrammar::class);
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getDatabaseName')->andReturn('db');
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $connection->shouldReceive('getConfig')->with('database')->andReturn('db');
        $connection->shouldReceive('getConfig')->with('schema')->andReturn('schema');
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $grammar->shouldReceive('compileColumnListing')->once()->andReturn('sql');
        $processor->shouldReceive('processColumnListing')->once()->andReturn(['column']);
        $builder = new PostgresBuilder($connection);
        $connection->shouldReceive('getTablePrefix')->once()->andReturn('prefix_');
        $connection->shouldReceive('selectFromWriteConnection')->once()->with('sql', ['db', 'public', 'prefix_table'])->andReturn(['column']);

        $this->assertEquals(['column'], $builder->getColumnListing('table'));
    }
}
