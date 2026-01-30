<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Processors\PostgresProcessor;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabasePostgresQueryGrammarTest extends TestCase
{
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

    protected function getBuilder(): Builder
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getDatabaseName')->andReturn('database');

        $grammar = new PostgresGrammar($connection);
        $processor = m::mock(PostgresProcessor::class);

        return new Builder($connection, $grammar, $processor);
    }

    public function testWhereDayIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereDayIn('created_at', [1, 15, 30]);

        $this->assertEquals(
            'select * from "users" where cast(extract(day from "created_at") as integer) in (?, ?, ?)',
            $builder->toSql()
        );
        $this->assertEquals([1, 15, 30], $builder->getBindings());
    }

    public function testOrWhereDayIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')
            ->where('id', 1)
            ->orWhereDayIn('created_at', [1, 12]);

        $this->assertEquals(
            'select * from "users" where "id" = ? or cast(extract(day from "created_at") as integer) in (?, ?)',
            $builder->toSql()
        );
    }

    public function testWhereDayNotIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereDayNotIn('created_at', [2, 4]);

        $this->assertEquals(
            'select * from "users" where cast(extract(day from "created_at") as integer) not in (?, ?)',
            $builder->toSql()
        );
        $this->assertEquals([2, 4], $builder->getBindings());
    }

    public function testWhereMonthIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereMonthIn('created_at', [1, 6, 12]);

        $this->assertEquals(
            'select * from "users" where cast(extract(month from "created_at") as integer) in (?, ?, ?)',
            $builder->toSql()
        );
        $this->assertEquals([1, 6, 12], $builder->getBindings());
    }

    public function testOrWhereMonthIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')
            ->where('id', 1)
            ->orWhereMonthIn('created_at', [1, 12]);

        $this->assertEquals(
            'select * from "users" where "id" = ? or cast(extract(month from "created_at") as integer) in (?, ?)',
            $builder->toSql()
        );
    }

    public function testWhereMonthNotIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereMonthNotIn('created_at', [1, 2]);

        $this->assertEquals(
            'select * from "users" where cast(extract(month from "created_at") as integer) not in (?, ?)',
            $builder->toSql()
        );
        $this->assertEquals([1, 2], $builder->getBindings());
    }

    public function testWhereYearIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereYearIn('created_at', [2023, 2024]);

        $this->assertEquals(
            'select * from "users" where cast(extract(year from "created_at") as integer) in (?, ?)',
            $builder->toSql()
        );
        $this->assertEquals([2023, 2024], $builder->getBindings());
    }
    public function testOrWhereYearIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')
            ->where('id', 1)
            ->orWhereYearIn('created_at', [2023, 2024]);

        $this->assertEquals(
            'select * from "users" where "id" = ? or cast(extract(year from "created_at") as integer) in (?, ?)',
            $builder->toSql()
        );
        $this->assertEquals([1, 2023, 2024], $builder->getBindings());
    }

    public function testWhereYearNotIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereYearNotIn('created_at', [2023, 2024]);

        $this->assertEquals(
            'select * from "users" where cast(extract(year from "created_at") as integer) not in (?, ?)',
            $builder->toSql()
        );
        $this->assertEquals([2023, 2024], $builder->getBindings());
    }

    public function testMultipleDateBasedWhereIns()
    {
        $builder = $this->getBuilder();

        $builder->select('*')->from('users')
            ->whereYearIn('created_at', [2024])
            ->whereMonthIn('created_at', [1, 2])
            ->whereDayIn('created_at', [10, 20]);

        $this->assertSame(
            'select * from "users" where cast(extract(year from "created_at") as integer) in (?) and cast(extract(month from "created_at") as integer) in (?, ?) and cast(extract(day from "created_at") as integer) in (?, ?)',
            $builder->toSql()
        );

        $this->assertEquals([2024, 1, 2, 10, 20], $builder->getBindings());
    }

    public function testMultipleDateBasedOrWhereIns()
    {
        $builder = $this->getBuilder();

        $builder->select('*')->from('users')
            ->whereYearIn('created_at', [2023])
            ->orWhereMonthIn('created_at', [5, 6])
            ->orWhereDayIn('created_at', [10, 20]);

        $this->assertSame(
            'select * from "users" where cast(extract(year from "created_at") as integer) in (?) or cast(extract(month from "created_at") as integer) in (?, ?) or cast(extract(day from "created_at") as integer) in (?, ?)',
            $builder->toSql()
        );

        $this->assertEquals(
            [2023, 5, 6, 10, 20],
            $builder->getBindings()
        );
    }

    public function testWhereDayInCollection()
    {
        $builder = $this->getBuilder();

        // Testing that passing a Collection works just like an array
        $builder->select('*')->from('users')->whereDayIn('created_at', collect([5, 10]));

        $this->assertSame(
            'select * from "users" where cast(extract(day from "created_at") as integer) in (?, ?)',
            $builder->toSql()
        );
        $this->assertEquals([5, 10], $builder->getBindings());
    }

    public function testWhereDayInSubquery()
    {
        $builder = $this->getBuilder();

        $builder->select('*')->from('users')->whereDayIn('created_at', function ($query) {
            $query->select('day')->from('holidays');
        });

        $this->assertSame(
            'select * from "users" where cast(extract(day from "created_at") as integer) in (select "day" from "holidays")',
            $builder->toSql()
        );
    }
}
