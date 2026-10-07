<?php

namespace Illuminate\Tests\Database;

use JMac\Testing\Double;
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
        $connection = Double::for(Connection::class);
        $grammar = new MySqlGrammar($connection);
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $builder = new MySqlBuilder($connection);
        $connection->expects('getTablePrefix')->returns('prefix_');
        $connection->expects('scalar')->with($grammar->compileTableExists(null, 'prefix_table'))->returns(1);

        $this->assertTrue($builder->hasTable('table'));
    }

    public function testGetColumnListing()
    {
        $connection = Double::for(Connection::class);
        $grammar = new MySqlGrammar($connection);
        $processor = new MySqlProcessor;
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('getPostProcessor')->returns($processor);
        $builder = new MySqlBuilder($connection);
        $connection->expects('getTablePrefix')->returns('prefix_');
        $connection->expects('selectFromWriteConnection')->with($grammar->compileColumns(null, 'prefix_table'))->returns([(object) ['name' => 'column', 'type_name' => 'int', 'type' => 'int', 'collation' => null, 'nullable' => 'YES', 'default' => null, 'comment' => null, 'expression' => null, 'extra' => '']]);

        $this->assertEquals(['column'], $builder->getColumnListing('table'));
    }
}
