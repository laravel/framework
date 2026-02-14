<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\MariaDbGrammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseMariaDbQueryGrammarTest extends TestCase
{
    public function testToRawSql()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('escape')->with('foo', false)->andReturn("'foo'");
        $grammar = new MariaDbGrammar($connection);

        $query = $grammar->substituteBindingsIntoRawSql(
            'select * from "users" where \'Hello\\\'World?\' IS NOT NULL AND "email" = ?',
            ['foo'],
        );

        $this->assertSame('select * from "users" where \'Hello\\\'World?\' IS NOT NULL AND "email" = \'foo\'', $query);
    }

    public function testTimeout()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('email', 'like', '%test%')->timeout(60);
        $this->assertSame(
            'SET STATEMENT max_statement_time=60 FOR select * from `users` where `email` like ?',
            $builder->toSql()
        );
    }

    public function testTimeoutWithDistinct()
    {
        $builder = $this->getBuilder();
        $builder->distinct()->select('*')->from('users')->timeout(30);
        $this->assertSame(
            'SET STATEMENT max_statement_time=30 FOR select distinct * from `users`',
            $builder->toSql()
        );
    }

    public function testTimeoutWithAggregate()
    {
        $builder = $this->getBuilder();
        $builder->from('users')->timeout(10);
        $builder->aggregate = ['function' => 'count', 'columns' => ['*']];
        $this->assertSame(
            'SET STATEMENT max_statement_time=10 FOR select count(*) as aggregate from `users`',
            $builder->toSql()
        );
    }

    public function testTimeoutWithExists()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('email', 'like', '%test%')->timeout(60);
        $this->assertSame(
            'SET STATEMENT max_statement_time=60 FOR select exists(select * from `users` where `email` like ?) as `exists`',
            $builder->getGrammar()->compileExists($builder)
        );
    }

    protected function getBuilder()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getDatabaseName')->andReturn('database');
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $grammar = new MariaDbGrammar($connection);
        $processor = m::mock(Processor::class);

        return new Builder($connection, $grammar, $processor);
    }
}
