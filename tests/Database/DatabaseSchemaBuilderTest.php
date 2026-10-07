<?php

namespace Illuminate\Tests\Database;

use JMac\Testing\Double;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Grammars\Grammar;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseSchemaBuilderTest extends TestCase
{
    public function testCreateDatabase()
    {
        $connection = Double::for(Connection::class);
        $grammar = new class($connection) extends Grammar {
        };
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('statement')->with($grammar->compileCreateDatabase('foo'))->andReturnTrue();
        $builder = new Builder($connection);

        $this->assertTrue($builder->createDatabase('foo'));
    }

    public function testDropDatabaseIfExists()
    {
        $connection = Double::for(Connection::class);
        $grammar = new class($connection) extends Grammar {
        };
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('statement')->with($grammar->compileDropDatabaseIfExists('foo'))->andReturnTrue();
        $builder = new Builder($connection);

        $this->assertTrue($builder->dropDatabaseIfExists('foo'));
    }

    public function testHasTableCorrectlyCallsGrammar()
    {
        $connection = Double::for(Connection::class);
        $grammar = Double::for(Grammar::class);
        $processor = Double::for(Processor::class);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('getPostProcessor')->andReturn($processor);
        $builder = new Builder($connection);
        $grammar->expects('compileTableExists');
        $grammar->expects('compileTables')->andReturn('sql');
        $processor->expects('processTables')->andReturn([['name' => 'prefix_table']]);
        $connection->expects('getTablePrefix')->andReturn('prefix_');
        $connection->expects('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'prefix_table']]);

        $this->assertTrue($builder->hasTable('table'));
    }

    public function testTableHasColumns()
    {
        $connection = Double::for(Connection::class);
        $grammar = Double::for(Grammar::class);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $builder = Double::for(Builder::class)->passthru(new Builder($connection));
        $builder->expects('getColumnListing')->with('users')->times(2)->andReturn(['id', 'firstname']);

        $this->assertTrue($builder->hasColumns('users', ['id', 'firstname']));
        $this->assertFalse($builder->hasColumns('users', ['id', 'address']));
    }

    public function testGetColumnTypeAddsPrefix()
    {
        $connection = Double::for(Connection::class);
        $grammar = new class($connection) extends Grammar
        {
            public function compileColumns($schema, $table)
            {
                return "columns for {$table}";
            }
        };
        $processor = new Processor;
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('getPostProcessor')->andReturn($processor);
        $builder = new Builder($connection);
        $connection->expects('getTablePrefix')->andReturn('prefix_');
        $connection->expects('selectFromWriteConnection')->with('columns for prefix_users')->andReturn([['name' => 'id', 'type_name' => 'integer']]);

        $this->assertSame('integer', $builder->getColumnType('users', 'id'));
    }
}
