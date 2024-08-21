<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseSqlServerQueryGrammarTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testToRawSql()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('escape')->with('foo', false)->andReturn("'foo'");
        $grammar = new SqlServerGrammar;
        $grammar->setConnection($connection);

        $query = $grammar->substituteBindingsIntoRawSql(
            "select * from [users] where 'Hello''World?' IS NOT NULL AND [email] = ?",
            ['foo'],
        );

        $this->assertSame("select * from [users] where 'Hello''World?' IS NOT NULL AND [email] = 'foo'", $query);
    }

    public function testOrderByPriority()
    {
        $grammar = new SqlServerGrammar;
        $queryString = $grammar->orderByPriority('name', ['john', 'doe']);
        $this->assertSame('case when [name] = ? then 0 when [name] = ? then 1 else 2 end asc', $queryString);
    }
}
