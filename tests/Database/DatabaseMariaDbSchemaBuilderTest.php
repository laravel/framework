<?php

namespace Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\MariaDbProcessor;
use Illuminate\Database\Schema\Grammars\MariaDbGrammar;
use Illuminate\Database\Schema\MariaDbBuilder;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseMariaDbSchemaBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testHasTable()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(MariaDbGrammar::class);
        $processor = m::mock(MariaDbProcessor::class);
        $connection->shouldReceive('getDatabaseName')->andReturn('db');
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $builder = new MariaDbBuilder($connection);
        $grammar->shouldReceive('compileTables')->once()->andReturn('sql');
        $processor->shouldReceive('processTables')->once()->andReturn([['name' => 'prefix_table']]);
        $connection->shouldReceive('getTablePrefix')->once()->andReturn('prefix_');
        $connection->shouldReceive('selectFromWriteConnection')->once()->with('sql')->andReturn([['name' => 'prefix_table']]);

        $this->assertTrue($builder->hasTable('table'));
    }

    public function testGetColumnListing()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(MariaDbGrammar::class);
        $processor = m::mock(MariaDbProcessor::class);
        $connection->shouldReceive('getDatabaseName')->andReturn('db');
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $grammar->shouldReceive('compileColumns')->with('db', 'prefix_table')->once()->andReturn('sql');
        $processor->shouldReceive('processColumns')->once()->andReturn([['name' => 'column']]);
        $builder = new MariaDbBuilder($connection);
        $connection->shouldReceive('getTablePrefix')->once()->andReturn('prefix_');
        $connection->shouldReceive('selectFromWriteConnection')->once()->with('sql')->andReturn([['name' => 'column']]);

        $this->assertEquals(['column'], $builder->getColumnListing('table'));
    }
}
