<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Processors\Processor;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseMySqlQueryGrammarTest extends TestCase
{
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

    public function testTimeout()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('email', 'like', '%test%')->timeout(60);
        $this->assertSame(
            'select /*+ MAX_EXECUTION_TIME(60000) */ * from `users` where `email` like ?',
            $builder->toSql()
        );
    }

    public function testTimeoutWithDistinct()
    {
        $builder = $this->getBuilder();
        $builder->distinct()->select('*')->from('users')->timeout(30);
        $this->assertSame(
            'select /*+ MAX_EXECUTION_TIME(30000) */ distinct * from `users`',
            $builder->toSql()
        );
    }

    public function testTimeoutWithAggregate()
    {
        $builder = $this->getBuilder();
        $builder->from('users')->timeout(10);
        $builder->aggregate = ['function' => 'count', 'columns' => ['*']];
        $this->assertSame(
            'select /*+ MAX_EXECUTION_TIME(10000) */ count(*) as aggregate from `users`',
            $builder->toSql()
        );
    }

    public function testTimeoutWithExists()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('email', 'like', '%test%')->timeout(60);
        $this->assertSame(
            'select /*+ MAX_EXECUTION_TIME(60000) */ exists(select * from `users` where `email` like ?) as `exists`',
            $builder->getGrammar()->compileExists($builder)
        );
    }

    public function testTimeoutNullRemovesTimeout()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->timeout(60)->timeout(null);
        $this->assertSame('select * from `users`', $builder->toSql());
    }

    public function testTimeoutThrowsExceptionForNegativeValue()
    {
        $this->expectException(InvalidArgumentException::class);

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->timeout(-1);
    }

    protected function getBuilder()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getDatabaseName')->andReturn('database');
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $grammar = new MySqlGrammar($connection);
        $processor = m::mock(Processor::class);

        return new Builder($connection, $grammar, $processor);
    }
}
