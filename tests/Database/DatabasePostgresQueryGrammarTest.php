<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabasePostgresQueryGrammarTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testToRawSql()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('escape')->with('foo', false)->andReturn("'foo'");
        $grammar = new PostgresGrammar($connection);

        $query = $grammar->substituteBindingsIntoRawSql(
            'select * from "users" where \'{}\' ?? \'Hello\\\'\\\'World?\' AND "email" = ?',
            ['foo'],
        );

        $this->assertSame('select * from "users" where \'{}\' ? \'Hello\\\'\\\'World?\' AND "email" = \'foo\'', $query);
    }

    public function testCustomOperators()
    {
        PostgresGrammar::customOperators(['@@@', '@>', '']);
        PostgresGrammar::customOperators(['@@>', 1]);

        $connection = m::mock(Connection::class);
        $grammar = new PostgresGrammar($connection);

        $operators = $grammar->getOperators();

        $this->assertIsList($operators);
        $this->assertContains('@@@', $operators);
        $this->assertContains('@@>', $operators);
        $this->assertNotContains('', $operators);
        $this->assertNotContains(1, $operators);
        $this->assertSame(array_unique($operators), $operators);
    }

    public function testCompileTruncate()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');

        $postgres = new PostgresGrammar($connection);
        $builder = m::mock(Builder::class);
        $builder->from = 'users';

        $this->assertEquals([
            'truncate "users" restart identity cascade' => [],
        ], $postgres->compileTruncate($builder));

        PostgresGrammar::cascadeOnTruncate(false);

        $this->assertEquals([
            'truncate "users" restart identity' => [],
        ], $postgres->compileTruncate($builder));

        PostgresGrammar::cascadeOnTruncate();

        $this->assertEquals([
            'truncate "users" restart identity cascade' => [],
        ], $postgres->compileTruncate($builder));
    }
    
    public function testCompileInsertOrUpdateUsing()
    {
        $connection = m::mock(\Illuminate\Database\Connection::class);
        $connection->shouldReceive('getPostProcessor')->andReturn(new \Illuminate\Database\Query\Processors\Processor());
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getQueryGrammar')->andReturnUsing(fn() => new PostgresGrammar($connection));

        $grammar = new PostgresGrammar($connection);

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
      $this->assertMatchesRegularExpression('/do\s+update/i', $sql);
    }
}
