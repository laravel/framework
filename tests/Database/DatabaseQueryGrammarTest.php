<?php

namespace Illuminate\Tests\Database;

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
        $grammar = new Grammar;
        $reflection = new ReflectionClass($grammar);
        $method = $reflection->getMethod('whereRaw');
        $expression = ['sql' => new Expression('select * from "users"')];

        $rawQuery = $method->invoke($grammar, $builder, $expression);

        $this->assertSame('select * from "users"', $rawQuery);
    }

    public function testWhereRawReturnsStringWhenStringPassed()
    {
        $builder = m::mock(Builder::class);
        $grammar = new Grammar;
        $reflection = new ReflectionClass($grammar);
        $method = $reflection->getMethod('whereRaw');
        $array = ['sql' => 'select * from "users"'];

        $rawQuery = $method->invoke($grammar, $builder, $array);

        $this->assertSame('select * from "users"', $rawQuery);
    }
}
