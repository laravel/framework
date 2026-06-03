<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Grammar;
use Illuminate\Database\Query\DialectExpression;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseDialectExpressionTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testThrowsOnEmptyDialects()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DialectExpression requires at least one dialect entry.');

        new DialectExpression([]);
    }

    public function testThrowsListsAllUnknownDriverKeysInMessage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('baddriver');
        $this->expectExceptionMessage('anotherbad');

        new DialectExpression(['baddriver' => 'sql', 'anotherbad' => 'sql']);
    }

    public function testAcceptsAllKnownDriverKeys()
    {
        // Should not throw — all supported drivers plus 'default'
        $expression = new DialectExpression([
            'mysql' => 'mysql_sql',
            'mariadb' => 'mariadb_sql',
            'pgsql' => 'pgsql_sql',
            'sqlite' => 'sqlite_sql',
            'sqlsrv' => 'sqlsrv_sql',
            'default' => 'default_sql',
        ]);

        $this->assertInstanceOf(DialectExpression::class, $expression);
    }

    public function testResolvesExactMatchForActiveDriver()
    {
        $expression = new DialectExpression([
            'mysql' => 'mysql_sql',
            'pgsql' => 'pgsql_sql',
            'sqlite' => 'sqlite_sql',
        ]);

        $this->assertSame('mysql_sql', $expression->getValue($this->mockGrammarWithDriver('mysql')));
        $this->assertSame('pgsql_sql', $expression->getValue($this->mockGrammarWithDriver('pgsql')));
        $this->assertSame('sqlite_sql', $expression->getValue($this->mockGrammarWithDriver('sqlite')));
    }

    public function testMariadbUsesExplicitEntryWhenPresent()
    {
        $expression = new DialectExpression([
            'mysql' => 'mysql_sql',
            'mariadb' => 'mariadb_sql',
            'pgsql' => 'pgsql_sql',
        ]);

        $this->assertSame('mariadb_sql', $expression->getValue($this->mockGrammarWithDriver('mariadb')));
    }

    public function testFallsBackToDefaultWhenDriverHasNoMapping()
    {
        $expression = new DialectExpression([
            'mysql' => 'mysql_sql',
            'default' => 'default_sql',
        ]);

        // pgsql is not mapped → should resolve to 'default'
        $this->assertSame('default_sql', $expression->getValue($this->mockGrammarWithDriver('pgsql')));
    }

    public function testThrowsWhenNoMappingAndNoDefault()
    {
        $this->expectException(\RuntimeException::class);

        $expression = new DialectExpression(['mysql' => 'mysql_sql']);
        $expression->getValue($this->mockGrammarWithDriver('pgsql'));
    }

    public function testExceptionMessageContainsActiveDriverName()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('pgsql');

        $expression = new DialectExpression(['mysql' => 'mysql_sql']);
        $expression->getValue($this->mockGrammarWithDriver('pgsql'));
    }

    public function testExceptionMessageListsRegisteredDrivers()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('mysql');

        $expression = new DialectExpression(['mysql' => 'mysql_sql']);
        $expression->getValue($this->mockGrammarWithDriver('sqlsrv'));
    }

    public function testDialectExpressionCompilesWithDifferentGrammars()
    {
        $expression = new DialectExpression([
            'mysql' => "DATE_FORMAT(created_at, '%Y-%m')",
            'mariadb' => "DATE_FORMAT(created_at, '%Y-%m-mariadb')",
            'pgsql' => "TO_CHAR(created_at, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', created_at)",
            'sqlsrv' => "FORMAT(created_at, 'yyyy-MM')",
        ]);

        $this->assertSame(
            "select strftime('%Y-%m', created_at) from \"users\"",
            $this->buildQuery('sqlite', \Illuminate\Database\Query\Grammars\SQLiteGrammar::class, $expression)
        );

        $this->assertSame(
            "select DATE_FORMAT(created_at, '%Y-%m') from `users`",
            $this->buildQuery('mysql', \Illuminate\Database\Query\Grammars\MySqlGrammar::class, $expression)
        );

        $this->assertSame(
            "select TO_CHAR(created_at, 'YYYY-MM') from \"users\"",
            $this->buildQuery('pgsql', \Illuminate\Database\Query\Grammars\PostgresGrammar::class, $expression)
        );

        $this->assertSame(
            "select FORMAT(created_at, 'yyyy-MM') from [users]",
            $this->buildQuery('sqlsrv', \Illuminate\Database\Query\Grammars\SqlServerGrammar::class, $expression)
        );

        $this->assertSame(
            "select DATE_FORMAT(created_at, '%Y-%m-mariadb') from `users`",
            $this->buildQuery('mariadb', \Illuminate\Database\Query\Grammars\MySqlGrammar::class, $expression)
        );
    }

    protected function mockGrammarWithDriver(string $driver): Grammar
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->andReturn($driver);

        $grammar = m::mock(Grammar::class);
        $grammar->shouldReceive('getConnection')->andReturn($connection);

        return $grammar;
    }

    protected function buildQuery(string $driver, string $grammarClass, DialectExpression $expression): string
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getDatabaseName')->andReturn('database');
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getDriverName')->andReturn($driver);

        $grammar = new $grammarClass($connection);

        $builder = new \Illuminate\Database\Query\Builder(
            $connection,
            $grammar,
            m::mock(\Illuminate\Database\Query\Processors\Processor::class)
        );

        return $builder->select($expression)->from('users')->toSql();
    }
}
