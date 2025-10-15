<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor;

class DatabaseMySqlQueryGrammarTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testToRawSql()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('escape')->with('foo', false)->andReturn("'foo'");
        $grammar = new MySqlGrammar($connection);

        $query = $grammar->substituteBindingsIntoRawSql(
            'select * from "users" where \'Hello\\\'World?\' IS NOT NULL AND "email" = ?',
            ['foo'],
        );

        $this->assertSame('select * from "users" where \'Hello\\\'World?\' IS NOT NULL AND "email" = \'foo\'', $query);
    }

    public function testCompileInsertOrUpdateUsing()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getPostProcessor')->andReturn(new Processor());
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getQueryGrammar')->andReturn(new MySqlGrammar($connection));

        $grammar = new MySqlGrammar($connection);

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

        $this->assertStringStartsWith('insert into', strtolower($sql));
        $this->assertStringContainsString('select', strtolower($sql));
        $this->assertStringContainsString('on duplicate key update', strtolower($sql));
        $this->assertMatchesRegularExpression('/values\s*\(`?name`?\)/i', $sql); 
        $this->assertMatchesRegularExpression('/values\s*\(`?email`?\)/i', $sql);
    }
}
