<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\PostgresProcessor;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\PostgresBuilder;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabasePostgresSchemaBuilderTest extends TestCase
{
    public function testHasTable()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = new PostgresGrammar($connection);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $builder = new PostgresBuilder($connection);
        $connection->expects('getTablePrefix')->times(2)->andReturn('prefix_');
        $connection->expects('scalar')->with($grammar->compileTableExists(null, 'prefix_table'))->andReturn(1);
        $connection->expects('scalar')->with($grammar->compileTableExists('public', 'prefix_table'))->andReturn(1);

        $this->assertTrue($builder->hasTable('table'));
        $this->assertTrue($builder->hasTable('public.table'));
    }

    public function testGetColumnListing()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = new PostgresGrammar($connection);
        $connection->shouldReceive('getServerVersion')->andReturn('12.0.0');
        $processor = new PostgresProcessor;
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('getPostProcessor')->andReturn($processor);
        $builder = new PostgresBuilder($connection);
        $connection->expects('getTablePrefix')->andReturn('prefix_');
        $connection->expects('selectFromWriteConnection')->with($grammar->compileColumns(null, 'prefix_table'))
            ->andReturn([(object) ['name' => 'column', 'type_name' => 'int4', 'type' => 'integer', 'collation' => null, 'nullable' => 'YES', 'default' => null, 'comment' => null, 'generated' => null]]);

        $this->assertEquals(['column'], $builder->getColumnListing('table'));
    }
}
