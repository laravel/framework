<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\MariaDbProcessor;
use Illuminate\Database\Schema\Grammars\MariaDbGrammar;
use Illuminate\Database\Schema\MariaDbBuilder;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseMariaDbSchemaBuilderTest extends TestCase
{
    public function testHasTable()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = new MariaDbGrammar($connection);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $builder = new MariaDbBuilder($connection);
        $connection->expects('getTablePrefix')->andReturn('prefix_');
        $connection->expects('scalar')->with($grammar->compileTableExists(null, 'prefix_table'))->andReturn(1);

        $this->assertTrue($builder->hasTable('table'));
    }

    public function testGetColumnListing()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = new MariaDbGrammar($connection);
        $processor = new MariaDbProcessor;
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('getPostProcessor')->andReturn($processor);
        $builder = new MariaDbBuilder($connection);
        $connection->expects('getTablePrefix')->andReturn('prefix_');
        $connection->expects('selectFromWriteConnection')->with($grammar->compileColumns(null, 'prefix_table'))
            ->andReturn([(object) ['name' => 'column', 'type_name' => 'int', 'type' => 'int', 'collation' => null, 'nullable' => 'YES', 'default' => null, 'comment' => null, 'expression' => null, 'extra' => '']]);

        $this->assertEquals(['column'], $builder->getColumnListing('table'));
    }
}
