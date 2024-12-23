<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
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
        $expressionArray = ['sql' => new Expression('select * from "users"')];

        $rawQuery = $method->invoke($grammar, $builder, $expressionArray);

        $this->assertSame('select * from "users"', $rawQuery);
    }

    public function testWhereRawReturnsStringWhenStringPassed()
    {
        $builder = m::mock(Builder::class);
        $grammar = new Grammar;
        $reflection = new ReflectionClass($grammar);
        $method = $reflection->getMethod('whereRaw');
        $stringArray = ['sql' => 'select * from "users"'];

        $rawQuery = $method->invoke($grammar, $builder, $stringArray);

        $this->assertSame('select * from "users"', $rawQuery);
    }

    public function testCompileInsertSingleValue()
    {
        $builder = $this->getBuilder();
        $grammar = $builder->getGrammar();

        $sql = $grammar->compileInsert($builder, ['name' => 'John Doe', 'email' => 'johndoe@laravel.com']);
        $this->assertSame('insert into "users" ("name", "email") values (?, ?)', $sql);
    }

    public function testCompileInsertMultipleValues()
    {
        $builder = $this->getBuilder();
        $grammar = $builder->getGrammar();
        $values = [
            ['name' => 'John Doe', 'email' => 'john@doe.com'],
            ['name' => 'Alice Wong', 'email' => 'alice@wong.com'],
        ];

        $sql = $grammar->compileInsert($builder, $values);
        $this->assertSame('insert into "users" ("name", "email") values (?, ?), (?, ?)', $sql);
    }

    public function testCompileInsertSingleValueWhereFirstKeyIsArray()
    {
        $builder = $this->getBuilder();
        $grammar = $builder->getGrammar();
        $value = [
            'configuration' => [
                'dark_mode' => false,
                'language' => 'en',
            ],
            'name' => 'John Doe',
            'email' => 'john@doe.com',
        ];

        $sql = $grammar->compileInsert($builder, $value);

        $this->assertSame('insert into "users" ("configuration", "name", "email") values (?, ?, ?)', $sql);
    }

    public function testCompileInsertSingleValueWhereFirstKeyIsNotArray()
    {
        $builder = $this->getBuilder();
        $grammar = $builder->getGrammar();

        $value = [
            'name' => 'John Doe',
            'configuration' => [
                'dark_mode' => false,
                'language' => 'en',
            ],
            'email' => 'john@doe.com',
        ];

        $sql = $grammar->compileInsert($builder, $value);

        $this->assertSame('insert into "users" ("name", "configuration", "email") values (?, ?, ?)', $sql);
    }

    protected function getConnection()
    {
        return m::mock(ConnectionInterface::class);
    }

    protected function getBuilder($tableName = 'users')
    {
        $grammar = new Grammar;
        $processor = m::mock(Processor::class);

        $builder = new Builder($this->getConnection(), $grammar, $processor);
        $builder->from = $tableName;

        return $builder;
    }
}
