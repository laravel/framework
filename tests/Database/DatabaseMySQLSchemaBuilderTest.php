<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\MySqlProcessor;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\Schema\MySqlBuilder;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseMySQLSchemaBuilderTest extends TestCase
{
    public function testHasTable()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = Mockery::mock(MySqlGrammar::class);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $builder = new MySqlBuilder($connection);
        $grammar->expects('compileTableExists')->andReturn('sql');
        $connection->expects('getTablePrefix')->andReturn('prefix_');
        $connection->expects('scalar')->with('sql')->andReturn(1);

        $this->assertTrue($builder->hasTable('table'));
    }

    public function testGetColumnListing()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = Mockery::mock(MySqlGrammar::class);
        $processor = Mockery::mock(MySqlProcessor::class);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('getPostProcessor')->andReturn($processor);
        $grammar->expects('compileColumns')->with(null, 'prefix_table')->andReturn('sql');
        $processor->expects('processColumns')->andReturn([['name' => 'column']]);
        $builder = new MySqlBuilder($connection);
        $connection->expects('getTablePrefix')->andReturn('prefix_');
        $connection->expects('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'column']]);

        $this->assertEquals(['column'], $builder->getColumnListing('table'));
    }
}
