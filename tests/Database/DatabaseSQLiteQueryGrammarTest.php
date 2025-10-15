<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Query\Builder;

class DatabaseSQLiteQueryGrammarTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testToRawSql()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('escape')->with('foo', false)->andReturn("'foo'");
        $grammar = new SQLiteGrammar($connection);

        $query = $grammar->substituteBindingsIntoRawSql(
            'select * from "users" where \'Hello\'\'World?\' IS NOT NULL AND "email" = ?',
            ['foo'],
        );

        $this->assertSame('select * from "users" where \'Hello\'\'World?\' IS NOT NULL AND "email" = \'foo\'', $query);
    }

    public function testCompileInsertOrUpdateUsing()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getPostProcessor')->andReturn(new \Illuminate\Database\Query\Processors\Processor());
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getQueryGrammar')->andReturnUsing(fn() => new SQLiteGrammar($connection));

        $grammar = new SQLiteGrammar($connection);

        $builder = new Builder($connection, $grammar);
        $builder->from('users');

        $select = clone $builder;
        $select->from('imports')->select('id', 'name', 'email');

        $sql = $grammar->compileInsertOrUpdateUsing(
            $builder,
            ['id', 'name', 'email'],
            $select,
            ['name', 'email']
        );

        $this->assertMatchesRegularExpression('/insert\s+into/i', $sql);
        $this->assertMatchesRegularExpression('/on\s+conflict/i', $sql);
        $this->assertMatchesRegularExpression('/do\s+update\s+set/i', $sql);
    }
}
