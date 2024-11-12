<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\DmConnection;
use Illuminate\Database\Query\Processors\DmProcessor;
use Illuminate\Database\Schema\DmBuilder;
use Illuminate\Database\Schema\Grammars\DmGrammar;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseDmSchemaBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testHasTable()
    {
        $connection = m::mock(DmConnection::class);
        $grammar = m::mock(DmGrammar::class);

        $connection->shouldReceive('getSchema')->andReturn('SYSDBA');
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getConfig')->with('schema')->andReturn('SYSDBA');

        $builder = new DmBuilder($connection);
        $grammar->shouldReceive('compileTableExists')->twice()->andReturn('sql');
        $connection->shouldReceive('getTablePrefix')->twice()->andReturn('prefix_');
        $connection->shouldReceive('scalar')->twice()->with('sql')->andReturn(1);

        $this->assertTrue($builder->hasTable('table'));
        $this->assertTrue($builder->hasTable('SYSDBA.table'));
    }

    public function testGetColumnListing()
    {
        $connection = m::mock(DmConnection::class);
        $grammar = m::mock(DmGrammar::class);
        $processor = m::mock(DmProcessor::class);
        $connection->shouldReceive('getSchema')->andReturn('SYSDBA');
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $connection->shouldReceive('getConfig')->with('schema')->andReturn('SYSDBA');

        $grammar->shouldReceive('compileTableId')->twice()->once('SYSDBA', 'prefix_table')->andReturn('tableIdSql');
        $connection->shouldReceive('selectFromWriteConnection')->once()->with('tableIdSql')->andReturn([['ID' => 1000]]);

        $grammar->shouldReceive('compileIdentityColumns')->once()->with([['ID' => 1000]])->andReturn('idenSql');
        $connection->shouldReceive('selectFromWriteConnection')->once()->with('idenSql')->andReturn([['NAME' => 'column']]);

        $grammar->shouldReceive('compileColumns')->with('SYSDBA', 'prefix_table', [['ID' => 1000]])->once()->andReturn('tableSql');
        $processor->shouldReceive('processColumnsIncrementClassName')->once()->andReturn([['name' => 'column']]);

        $builder = new DmBuilder($connection);
        $connection->shouldReceive('getTablePrefix')->once()->andReturn('prefix_');
        $connection->shouldReceive('selectFromWriteConnection')->once()->with('tableSql')->andReturn([['NAME' => 'column']]);
        $processor->shouldReceive('processColumns')->once()->andReturn([['name' => 'column']]);

        $this->assertEquals(['0' => 'column'], $builder->getColumnListing('table'));
    }
}
