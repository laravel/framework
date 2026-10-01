<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DatabaseQueryGrammarTest extends TestCase
{
    public function testWhereRawReturnsStringWhenExpressionPassed()
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $grammar = new Grammar($connection);
        $builder = new Builder($connection, $grammar);
        $reflection = new ReflectionClass($grammar);
        $method = $reflection->getMethod('whereRaw');
        $expressionArray = ['sql' => new Expression('select * from "users"')];

        $rawQuery = $method->invoke($grammar, $builder, $expressionArray);

        $this->assertSame('select * from "users"', $rawQuery);
    }

    public function testWhereRawReturnsStringWhenStringPassed()
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $grammar = new Grammar($connection);
        $builder = new Builder($connection, $grammar);
        $reflection = new ReflectionClass($grammar);
        $method = $reflection->getMethod('whereRaw');
        $stringArray = ['sql' => 'select * from "users"'];

        $rawQuery = $method->invoke($grammar, $builder, $stringArray);

        $this->assertSame('select * from "users"', $rawQuery);
    }

    public function testCompileOrdersAcceptsExpression()
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $grammar = new Grammar($connection);
        $builder = new Builder($connection, $grammar);

        $orders = [
            ['sql' => new Expression('length("name") desc')], // mimics orderByRaw(DB::raw(...))
        ];

        $ref = new \ReflectionClass($grammar);
        $method = $ref->getMethod('compileOrders'); // protected
        $sql = $method->invoke($grammar, $builder, $orders);

        $this->assertSame('order by length("name") desc', strtolower($sql));
    }

    public function testCompileOrdersAcceptsExpressionWithPlaceholders()
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $grammar = new Grammar($connection);
        $builder = new Builder($connection, $grammar);

        $orders = [
            ['sql' => new Expression('field(status, ?, ?) asc')],
        ];

        $ref = new \ReflectionClass($grammar);
        $method = $ref->getMethod('compileOrders');
        $sql = $method->invoke($grammar, $builder, $orders);

        $this->assertSame('order by field(status, ?, ?) asc', strtolower($sql));
    }

    public function testWrap()
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $grammar = new Grammar($connection);

        $this->assertSame('"id"', $grammar->wrap('id'));
        $this->assertSame('*', $grammar->wrap('*'));
        $this->assertSame('"users"."id"', $grammar->wrap('users.id'));
        $this->assertSame('"users".*', $grammar->wrap('users.*'));
        $this->assertSame('"id" as "user_id"', $grammar->wrap('id as user_id'));
        $this->assertSame('"users"."id" as "user_id"', $grammar->wrap('users.id as user_id'));
        $this->assertSame('count(*)', $grammar->wrap(new Expression('count(*)')));
    }
}
