<?php

namespace Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\MariaDbGrammar;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseMariaDbQueryGrammarTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testToRawSql()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('escape')->with('foo', false)->andReturn("'foo'");
        $grammar = new MariaDbGrammar;
        $grammar->setConnection($connection);

        $query = $grammar->substituteBindingsIntoRawSql(
            'select * from "users" where \'Hello\\\'World?\' IS NOT NULL AND "email" = ?',
            ['foo'],
        );

        $this->assertSame('select * from "users" where \'Hello\\\'World?\' IS NOT NULL AND "email" = \'foo\'', $query);
    }

    public function testOrderByPriority()
    {
        $grammar = new MariaDbGrammar;
        $queryString = $grammar->orderByPriority('name', ['john', 'doe']);
        $this->assertSame('case when `name` = ? then 0 when `name` = ? then 1 else 2 end asc', $queryString);
    }
}
