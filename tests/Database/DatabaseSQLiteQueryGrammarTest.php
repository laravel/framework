<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Database\Query\Processors\SQLiteProcessor;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseSQLiteQueryGrammarTest extends TestCase
{
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

    protected function getBuilder(): Builder
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getDatabaseName')->andReturn('database');

        $grammar = new SQLiteGrammar($connection);
        $processor = m::mock(SQLiteProcessor::class);

        return new Builder($connection, $grammar, $processor);
    }

    public function testWhereDayIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereDayIn('created_at', [1, 15, 30]);

        $this->assertEquals(
            'select * from "users" where cast(strftime("%d", "created_at") as integer) in (?, ?, ?)',
            $builder->toSql()
        );
        $this->assertEquals([1, 15, 30], $builder->getBindings());
    }

    public function testOrWhereDayIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 1)->orWhereDayIn('created_at', [1, 15]);

        $this->assertEquals(
            'select * from "users" where "id" = ? or cast(strftime("%d", "created_at") as integer) in (?, ?)',
            $builder->toSql()
        );
        $this->assertEquals([1, 1, 15], $builder->getBindings());
    }

    public function testWhereDayNotIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereDayNotIn('created_at', [2, 3]);

        $this->assertEquals(
            'select * from "users" where cast(strftime("%d", "created_at") as integer) not in (?, ?)',
            $builder->toSql()
        );
        $this->assertEquals([2, 3], $builder->getBindings());
    }

    public function testWhereMonthIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereMonthIn('created_at', [1, 12, 6]);

        $this->assertEquals(
            'select * from "users" where cast(strftime("%m", "created_at") as integer) in (?, ?, ?)',
            $builder->toSql()
        );
        $this->assertEquals([1, 12, 6], $builder->getBindings());
    }

    public function testOrWhereMonthIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 1)->orWhereMonthIn('created_at', [1, 12]);

        $this->assertEquals(
            'select * from "users" where "id" = ? or cast(strftime("%m", "created_at") as integer) in (?, ?)',
            $builder->toSql()
        );
        $this->assertEquals([1, 1, 12], $builder->getBindings());
    }

    public function testWhereMonthNotIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereMonthNotIn('created_at', [2, 3]);

        $this->assertEquals(
            'select * from "users" where cast(strftime("%m", "created_at") as integer) not in (?, ?)',
            $builder->toSql()
        );
        $this->assertEquals([2, 3], $builder->getBindings());
    }

    public function testWhereYearIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereYearIn('created_at', [2012, 2015, 2030]);

        $this->assertEquals(
            'select * from "users" where cast(strftime("%Y", "created_at") as integer) in (?, ?, ?)',
            $builder->toSql()
        );
        $this->assertEquals([2012, 2015, 2030], $builder->getBindings());
    }

    public function testOrWhereYearIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 1)->orWhereYearIn('created_at', [2024, 2025]);

        $this->assertEquals(
            'select * from "users" where "id" = ? or cast(strftime("%Y", "created_at") as integer) in (?, ?)',
            $builder->toSql()
        );

        $this->assertEquals([1, 2024, 2025], $builder->getBindings());
    }

    public function testWhereYearNotIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereYearNotIn('created_at', [2024, 2025]);

        $this->assertEquals(
            'select * from "users" where cast(strftime("%Y", "created_at") as integer) not in (?, ?)',
            $builder->toSql()
        );
        $this->assertEquals([2024, 2025], $builder->getBindings());
    }

    public function testMultipleDateBasedWhereIns()
    {
        $builder = $this->getBuilder();

        $builder->select('*')->from('users')
            ->whereYearIn('created_at', [2024])
            ->whereMonthIn('created_at', [1, 2])
            ->whereDayIn('created_at', [10, 20]);

        $this->assertSame(
            'select * from "users" where cast(strftime("%Y", "created_at") as integer) in (?) and cast(strftime("%m", "created_at") as integer) in (?, ?) and cast(strftime("%d", "created_at") as integer) in (?, ?)',
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
            'select * from "users" where cast(strftime("%Y", "created_at") as integer) in (?) or cast(strftime("%m", "created_at") as integer) in (?, ?) or cast(strftime("%d", "created_at") as integer) in (?, ?)',
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

        // Testing Collection support for SQLite
        $builder->select('*')->from('users')->whereDayIn('created_at', collect([5, 10]));

        $this->assertSame(
            'select * from "users" where cast(strftime("%d", "created_at") as integer) in (?, ?)',
            $builder->toSql()
        );
        $this->assertEquals([5, 10], $builder->getBindings());
    }

    public function testWhereDayInSubquery()
    {
        $builder = $this->getBuilder();

        // Testing Subquery support for SQLite
        $builder->select('*')->from('users')->whereDayIn('created_at', function ($query) {
            $query->select('day')->from('holidays');
        });

        $this->assertSame(
            'select * from "users" where cast(strftime("%d", "created_at") as integer) in (select "day" from "holidays")',
            $builder->toSql()
        );
    }
}
