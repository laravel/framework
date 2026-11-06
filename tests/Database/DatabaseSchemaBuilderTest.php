<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Grammars\Grammar;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;

class DatabaseSchemaBuilderTest extends TestCase
{
    use VerifiesDoubles;

    public function testCreateDatabase()
    {
        $connection = Double::for(Connection::class);
        $grammar = new class($connection) extends Grammar {
        };
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('statement')->with($grammar->compileCreateDatabase('foo'))->returns(true);
        $builder = new Builder($connection);

        $this->assertTrue($builder->createDatabase('foo'));
    }

    public function testDropDatabaseIfExists()
    {
        $connection = Double::for(Connection::class);
        $grammar = new class($connection) extends Grammar {
        };
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('statement')->with($grammar->compileDropDatabaseIfExists('foo'))->returns(true);
        $builder = new Builder($connection);

        $this->assertTrue($builder->dropDatabaseIfExists('foo'));
    }

    public function testHasTableCorrectlyCallsGrammar()
    {
        $connection = Double::for(Connection::class);
        $grammar = Double::for(Grammar::class);
        $processor = Double::for(Processor::class);
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('getPostProcessor')->returns($processor);
        $builder = new Builder($connection);
        $grammar->expects('compileTableExists');
        $grammar->expects('compileTables')->returns('sql');
        $processor->expects('processTables')->returns([['name' => 'prefix_table']]);
        $connection->expects('getTablePrefix')->returns('prefix_');
        $connection->expects('selectFromWriteConnection')->with('sql')->returns([['name' => 'prefix_table']]);

        $this->assertTrue($builder->hasTable('table'));
    }

    public function testTableHasColumns()
    {
        $connection = Double::for(Connection::class);
        $grammar = Double::for(Grammar::class);
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $builder = Double::for(Builder::class)->passthru(new Builder($connection));
        $builder->expects('getColumnListing')->with('users')->times(2)->returns(['id', 'firstname']);

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
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('getPostProcessor')->returns($processor);
        $builder = new Builder($connection);
        $connection->expects('getTablePrefix')->returns('prefix_');
        $connection->expects('selectFromWriteConnection')->with('columns for prefix_users')->returns([['name' => 'id', 'type_name' => 'integer']]);

        $this->assertSame('integer', $builder->getColumnType('users', 'id'));
    }
}
