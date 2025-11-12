<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DatabaseQueryGrammarTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testWhereRawReturnsStringWhenExpressionPassed()
    {
        $builder = m::mock(Builder::class);
        $grammar = new Grammar(m::mock(Connection::class));
        $reflection = new ReflectionClass($grammar);
        $method = $reflection->getMethod('whereRaw');
        $expressionArray = ['sql' => new Expression('select * from "users"')];

        $rawQuery = $method->invoke($grammar, $builder, $expressionArray);

        $this->assertSame('select * from "users"', $rawQuery);
    }

    public function testWhereRawReturnsStringWhenStringPassed()
    {
        $builder = m::mock(Builder::class);
        $grammar = new Grammar(m::mock(Connection::class));
        $reflection = new ReflectionClass($grammar);
        $method = $reflection->getMethod('whereRaw');
        $stringArray = ['sql' => 'select * from "users"'];

        $rawQuery = $method->invoke($grammar, $builder, $stringArray);

        $this->assertSame('select * from "users"', $rawQuery);
    }

    public function testCompileOrdersAcceptsExpression()
    {
        $builder = m::mock(Builder::class);
        $grammar = new Grammar(m::mock(Connection::class));

        // compileOrders() calls $query->getGrammar() → return our $grammar
        $builder->shouldReceive('getGrammar')->andReturn($grammar);

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
        $builder = m::mock(Builder::class);
        $grammar = new Grammar(m::mock(Connection::class));
        $builder->shouldReceive('getGrammar')->andReturn($grammar);

        $orders = [
            ['sql' => new Expression('field(status, ?, ?) asc')],
        ];

        $ref = new \ReflectionClass($grammar);
        $method = $ref->getMethod('compileOrders');
        $sql = $method->invoke($grammar, $builder, $orders);

        $this->assertSame('order by field(status, ?, ?) asc', strtolower($sql));
    }
}
