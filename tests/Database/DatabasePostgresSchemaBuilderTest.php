<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\PostgresProcessor;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\PostgresBuilder;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;

class DatabasePostgresSchemaBuilderTest extends TestCase
{
    use VerifiesDoubles;

    public function testHasTable()
    {
        $connection = Double::for(Connection::class);
        $grammar = new PostgresGrammar($connection);
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $builder = new PostgresBuilder($connection);
        $connection->expects('getTablePrefix')->times(2)->returns('prefix_');
        $connection->expects('scalar')->with($grammar->compileTableExists(null, 'prefix_table'))->returns(1);
        $connection->expects('scalar')->with($grammar->compileTableExists('public', 'prefix_table'))->returns(1);

        $this->assertTrue($builder->hasTable('table'));
        $this->assertTrue($builder->hasTable('public.table'));
    }

    public function testGetColumnListing()
    {
        $connection = Double::for(Connection::class);
        $grammar = new PostgresGrammar($connection);
        $connection->allows('getServerVersion')->returns('12.0.0');
        $processor = new PostgresProcessor;
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('getPostProcessor')->returns($processor);
        $builder = new PostgresBuilder($connection);
        $connection->expects('getTablePrefix')->returns('prefix_');
        $connection->expects('selectFromWriteConnection')->with($grammar->compileColumns(null, 'prefix_table'))->returns([(object) ['name' => 'column', 'type_name' => 'int4', 'type' => 'integer', 'collation' => null, 'nullable' => 'YES', 'default' => null, 'comment' => null, 'generated' => null]]);

        $this->assertEquals(['column'], $builder->getColumnListing('table'));
    }
}
