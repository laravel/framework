<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\MariaDbGrammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseMariaDbQueryGrammarTest extends TestCase
{
    public function testToRawSql()
    {
        $connection = Mockery::mock(Connection::class);
        $connection->expects('escape')->with('foo', false)->andReturn("'foo'");
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
            'set statement max_statement_time=60 for select * from `users` where `email` like ?',
            $builder->toSql()
        );
    }

    public function testTimeoutWithAggregate()
    {
        $builder = $this->getBuilder();
        $builder->from('users')->timeout(10);
        $builder->aggregate = ['function' => 'count', 'columns' => ['*']];
        $this->assertSame(
            'set statement max_statement_time=10 for select count(*) as `aggregate` from `users`',
            $builder->toSql()
        );
    }

    public function testTimeoutWithUnion()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->timeout(10)->union($this->getBuilder()->select('*')->from('admins'));
        $this->assertSame(
            'set statement max_statement_time=10 for (select * from `users`) union (select * from `admins`)',
            $builder->toSql()
        );
    }

    public function testTimeoutWithUnionAggregate()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->timeout(10)->union($this->getBuilder()->select('*')->from('admins'));
        $builder->aggregate = ['function' => 'count', 'columns' => ['*']];
        $this->assertSame(
            'set statement max_statement_time=10 for select count(*) as `aggregate` from ((select * from `users`) union (select * from `admins`)) as `temp_table`',
            $builder->toSql()
        );
    }

    public function testTimeoutWithExists()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->timeout(10);
        $this->assertSame(
            'set statement max_statement_time=10 for select exists(select * from `users`) as `exists`',
            $builder->getGrammar()->compileExists($builder)
        );
    }

    public function testTimeoutNullRemovesTimeout()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->timeout(60)->timeout(null);
        $this->assertSame('select * from `users`', $builder->toSql());
    }

    protected function getBuilder()
    {
        $connection = new Connection(new PDO('sqlite::memory:'), 'database');
        $grammar = new MariaDbGrammar($connection);
        $processor = new Processor;

        return new Builder($connection, $grammar, $processor);
    }
}
