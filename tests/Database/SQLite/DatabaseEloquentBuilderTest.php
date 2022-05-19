<?php

namespace Illuminate\Tests\Database\SQLite;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Database\Query\Processors\SQLiteProcessor;
use Illuminate\Support\Carbon;
use Illuminate\Tests\Database\EloquentBuilderTestHigherOrderWhereScopeStub;
use Illuminate\Tests\Database\EloquentBuilderTestNestedStub;
use Illuminate\Tests\Database\EloquentBuilderTestStub;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow(null);

        m::close();
    }

    public function testRealNestedWhereWithScopes()
    {
        $model = new EloquentBuilderTestNestedStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->where('foo', '=', 'bar')->where(function ($query) {
            $query->where('baz', '>', 9000);
        });
        $this->assertSame('select * from "table" where "foo" = ? and ("baz" > ?) and "table"."deleted_at" is null', $query->toSql());
        $this->assertEquals(['bar', 9000], $query->getBindings());
    }

    public function testRealNestedWhereWithScopesMacro()
    {
        $model = new EloquentBuilderTestNestedStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->where('foo', '=', 'bar')->where(function ($query) {
            $query->where('baz', '>', 9000)->onlyTrashed();
        })->withTrashed();
        $this->assertSame('select * from "table" where "foo" = ? and ("baz" > ? and "table"."deleted_at" is not null)', $query->toSql());
        $this->assertEquals(['bar', 9000], $query->getBindings());
    }

    public function testRealNestedWhereWithMultipleScopesAndOneDeadScope()
    {
        $model = new EloquentBuilderTestNestedStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->empty()->where('foo', '=', 'bar')->empty()->where(function ($query) {
            $query->empty()->where('baz', '>', 9000);
        });
        $this->assertSame('select * from "table" where "foo" = ? and ("baz" > ?) and "table"."deleted_at" is null', $query->toSql());
        $this->assertEquals(['bar', 9000], $query->getBindings());
    }

    public function testSimpleWhereNot()
    {
        $model = new EloquentBuilderTestStub();
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->whereNot('name', 'foo')->whereNot('name', '<>', 'bar');
        $this->assertEquals('select * from "table" where not "name" = ? and not "name" <> ?', $query->toSql());
        $this->assertEquals(['foo', 'bar'], $query->getBindings());
    }

    public function testSimpleOrWhereNot()
    {
        $model = new EloquentBuilderTestStub();
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->orWhereNot('name', 'foo')->orWhereNot('name', '<>', 'bar');
        $this->assertEquals('select * from "table" where not "name" = ? or not "name" <> ?', $query->toSql());
        $this->assertEquals(['foo', 'bar'], $query->getBindings());
    }

    public function testRealQueryHigherOrderOrWhereScopes()
    {
        $model = new EloquentBuilderTestHigherOrderWhereScopeStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->one()->orWhere->two();
        $this->assertSame('select * from "table" where "one" = ? or ("two" = ?)', $query->toSql());
    }

    public function testRealQueryChainedHigherOrderOrWhereScopes()
    {
        $model = new EloquentBuilderTestHigherOrderWhereScopeStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->one()->orWhere->two()->orWhere->three();
        $this->assertSame('select * from "table" where "one" = ? or ("two" = ?) or ("three" = ?)', $query->toSql());
    }

    public function testRealQueryHigherOrderWhereNotScopes()
    {
        $model = new EloquentBuilderTestHigherOrderWhereScopeStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->one()->whereNot->two();
        $this->assertSame('select * from "table" where "one" = ? and not ("two" = ?)', $query->toSql());
    }

    public function testRealQueryChainedHigherOrderWhereNotScopes()
    {
        $model = new EloquentBuilderTestHigherOrderWhereScopeStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->one()->whereNot->two()->whereNot->three();
        $this->assertSame('select * from "table" where "one" = ? and not ("two" = ?) and not ("three" = ?)', $query->toSql());
    }

    public function testRealQueryHigherOrderOrWhereNotScopes()
    {
        $model = new EloquentBuilderTestHigherOrderWhereScopeStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->one()->orWhereNot->two();
        $this->assertSame('select * from "table" where "one" = ? or not ("two" = ?)', $query->toSql());
    }

    public function testRealQueryChainedHigherOrderOrWhereNotScopes()
    {
        $model = new EloquentBuilderTestHigherOrderWhereScopeStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->one()->orWhereNot->two()->orWhereNot->three();
        $this->assertSame('select * from "table" where "one" = ? or not ("two" = ?) or not ("three" = ?)', $query->toSql());
    }

    protected function mockConnectionForModel($model)
    {
        $grammar = new SQLiteGrammar;
        $processor = new SQLiteProcessor;
        $connection = m::mock(ConnectionInterface::class, ['getQueryGrammar' => $grammar, 'getPostProcessor' => $processor]);
        $connection->shouldReceive('query')->andReturnUsing(function () use ($connection, $grammar, $processor) {
            return new BaseBuilder($connection, $grammar, $processor);
        });
        $connection->shouldReceive('getDatabaseName')->andReturn('database');
        $resolver = m::mock(ConnectionResolverInterface::class, ['connection' => $connection]);
        $class = get_class($model);
        $class::setConnectionResolver($resolver);
    }
}
