<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scopes\Scope;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentLocalScopesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        tap(new DB)->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ])->bootEloquent();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Model::unsetConnectionResolver();
    }

    public function testCanCheckExistenceOfLocalScope()
    {
        $model = new EloquentLocalScopesTestModel;

        $this->assertTrue($model->hasNamedScope('active'));
        $this->assertTrue($model->hasNamedScope('type'));

        $this->assertFalse($model->hasNamedScope('nonExistentLocalScope'));
    }

    public function testLocalScopeIsApplied()
    {
        $model = new EloquentLocalScopesTestModel;
        $query = $model->newQuery()->active();

        $this->assertSame('select * from "table" where "active" = ?', $query->toSql());
        $this->assertEquals([true], $query->getBindings());
    }

    public function testDynamicLocalScopeIsApplied()
    {
        $model = new EloquentLocalScopesTestModel;
        $query = $model->newQuery()->type('foo');

        $this->assertSame('select * from "table" where "type" = ?', $query->toSql());
        $this->assertEquals(['foo'], $query->getBindings());
    }

    public function testLocalScopesCanChained()
    {
        $model = new EloquentLocalScopesTestModel;
        $query = $model->newQuery()->active()->type('foo');

        $this->assertSame('select * from "table" where "active" = ? and "type" = ?', $query->toSql());
        $this->assertEquals([true, 'foo'], $query->getBindings());
    }

    public function testNewSyntaxLocalScopeNewerThanIsApplied()
    {
        $model = new EloquentLocalScopesTestModel;
        $query = $model->newQuery()->newerThan('2020-01-01');

        $this->assertSame('select * from "table" where "created_at" > ?', $query->toSql());
        $this->assertEquals(['2020-01-01'], $query->getBindings());
    }

    public function testNewSyntaxCanBeChainedTogether()
    {
        $model = new EloquentLocalScopesTestModel;
        $query = $model->newQuery()->newerThan('2020-01-01')->greaterThan(10);

        $this->assertSame('select * from "table" where "created_at" > ? and "number" > ?', $query->toSql());
        $this->assertEquals(['2020-01-01', 10], $query->getBindings());
    }

    public function testNewSyntaxCanBeChainedWithOldSyntax()
    {
        $model = new EloquentLocalScopesTestModel;
        $query = $model->newQuery()->newerThan('2020-01-01')->active();

        $this->assertSame('select * from "table" where "created_at" > ? and "active" = ?', $query->toSql());
        $this->assertEquals(['2020-01-01', true], $query->getBindings());
    }

}

class EloquentLocalScopesTestModel extends Model
{
    protected $table = 'table';

    public function scopeActive($query)
    {
        $query->where('active', true);
    }

    public function scopeType($query, $type)
    {
        $query->where('type', $type);
    }

   public function newerThan(Builder $query, $date): Scope
   {
        return Scope::make(
            apply: function () use ($query, $date) {
                return $query->where('created_at', '>', $date);
            },
        );
   }


   public function greaterThan(Builder $query, int $int): Scope
   {
        return Scope::make(
            apply: function () use ($query, $int) {
                return $query->where('number', '>', $int);
            },
        );
   }

}
