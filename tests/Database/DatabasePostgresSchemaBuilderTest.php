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
    public function testHasTable()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(PostgresGrammar::class);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $builder = new PostgresBuilder($connection);
        $grammar->expects('compileTableExists')->times(2)->andReturn('sql');
        $connection->expects('getTablePrefix')->times(2)->andReturn('prefix_');
        $connection->expects('scalar')->times(2)->with('sql')->andReturn(1);

        $this->assertTrue($builder->hasTable('table'));
        $this->assertTrue($builder->hasTable('public.table'));
    }

    public function testGetColumnListing()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(PostgresGrammar::class);
        $processor = m::mock(PostgresProcessor::class);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('getPostProcessor')->andReturn($processor);
        $grammar->expects('compileColumns')->with(null, 'prefix_table')->andReturn('sql');
        $processor->expects('processColumns')->andReturn([['name' => 'column']]);
        $builder = new PostgresBuilder($connection);
        $connection->expects('getTablePrefix')->andReturn('prefix_');
        $connection->expects('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'column']]);

        $this->assertEquals(['column'], $builder->getColumnListing('table'));
    }
}
