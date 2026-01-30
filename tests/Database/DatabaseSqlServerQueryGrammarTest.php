<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;
use Illuminate\Database\Query\Processors\SqlServerProcessor;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseSqlServerQueryGrammarTest extends TestCase
{
    public function testToRawSql()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('escape')->with('foo', false)->andReturn("'foo'");
        $grammar = new SqlServerGrammar($connection);

        $query = $grammar->substituteBindingsIntoRawSql(
            "select * from [users] where 'Hello''World?' IS NOT NULL AND [email] = ?",
            ['foo'],
        );

        $this->assertSame("select * from [users] where 'Hello''World?' IS NOT NULL AND [email] = 'foo'", $query);
    }

    protected function getBuilder(): Builder
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getDatabaseName')->andReturn('database');

        $grammar = new SqlServerGrammar($connection);
        $processor = m::mock(SqlServerProcessor::class);

        return new Builder($connection, $grammar, $processor);
    }

    public function testWhereDayIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereDayIn('created_at', [1, 15, 30]);

        $this->assertEquals(
            'select * from [users] where datepart(day, [created_at]) in (?, ?, ?)',
            $builder->toSql()
        );
    }

    public function testOrWhereDayIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 1)->orWhereDayIn('created_at', [3, 5]);

        $this->assertEquals(
            'select * from [users] where [id] = ? or datepart(day, [created_at]) in (?, ?)',
            $builder->toSql()
        );
    }

    public function testWhereDayNotIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereDayNotIn('created_at', [2024, 2025], 'and', true);

        $this->assertEquals(
            'select * from [users] where datepart(day, [created_at]) not in (?, ?)',
            $builder->toSql()
        );
    }

    public function testWhereMonthIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereMonthIn('created_at', [1, 3, 4]);

        $this->assertEquals(
            'select * from [users] where datepart(month, [created_at]) in (?, ?, ?)',
            $builder->toSql()
        );
    }

    public function testOrWhereMonthIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 1)->orWhereMonthIn('created_at', [1, 12]);

        $this->assertEquals(
            'select * from [users] where [id] = ? or datepart(month, [created_at]) in (?, ?)',
            $builder->toSql()
        );
    }

    public function testWhereMonthNotIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereMonthNotIn('created_at', [2, 9], 'and', true);

        $this->assertEquals(
            'select * from [users] where datepart(month, [created_at]) not in (?, ?)',
            $builder->toSql()
        );
    }

    public function testWhereYearIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereYearIn('created_at', [2015, 2029, 2030]);

        $this->assertEquals(
            'select * from [users] where datepart(year, [created_at]) in (?, ?, ?)',
            $builder->toSql()
        );
    }

    public function testOrWhereYearIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 1)->orWhereYearIn('created_at', [2005, 2012]);

        $this->assertEquals(
            'select * from [users] where [id] = ? or datepart(year, [created_at]) in (?, ?)',
            $builder->toSql()
        );
    }

    public function testWhereYearNotIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereYearIn('created_at', [2024, 2025], 'and', true);

        $this->assertEquals(
            'select * from [users] where datepart(year, [created_at]) not in (?, ?)',
            $builder->toSql()
        );
    }
    public function testMultipleDateBasedWhereIns()
    {
        $builder = $this->getBuilder();

        $builder->select('*')->from('users')
            ->whereYearIn('created_at', [2024])
            ->whereMonthIn('created_at', [1, 2])
            ->whereDayIn('created_at', [10, 20]);

        $this->assertSame(
            'select * from [users] where datepart(year, [created_at]) in (?) and datepart(month, [created_at]) in (?, ?) and datepart(day, [created_at]) in (?, ?)',
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
            'select * from [users] where datepart(year, [created_at]) in (?) or datepart(month, [created_at]) in (?, ?) or datepart(day, [created_at]) in (?, ?)',
            $builder->toSql()
        );

        $this->assertEquals(
            [2023, 5, 6, 10, 20],
            $builder->getBindings()
        );
    }
}
