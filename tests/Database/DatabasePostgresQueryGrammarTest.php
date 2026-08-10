<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabasePostgresQueryGrammarTest extends TestCase
{
    public function testToRawSql()
    {
        $connection = Mockery::mock(Connection::class);
        $connection->expects('escape')->with('foo', false)->andReturn("'foo'");
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

        $connection = Mockery::mock(Connection::class);
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
        $connection = Mockery::mock(Connection::class);
        $connection->expects('getTablePrefix')->times(3)->andReturn('');

        $postgres = new PostgresGrammar($connection);
        $builder = Mockery::mock(Builder::class);
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
}
