<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
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

    public function testLocalScopeNestingDoesntDoubleFirstWhereClauseNegation()
    {
        $model = new EloquentLocalScopesTestModel;
        $query = $model
            ->newQuery()
            ->whereNot('firstWhere', true)
            ->orWhere('secondWhere', true)
            ->active();

        $this->assertSame('select * from "table" where (not "firstWhere" = ? or "secondWhere" = ?) and "active" = ?', $query->toSql());
        $this->assertEquals([true, true, true], $query->getBindings());
    }

    public function testLocalScopeNestingGroupsOrNotWhereClause()
    {
        $model = new EloquentLocalScopesTestModel;
        $query = $model
            ->newQuery()
            ->where('firstWhere', true)
            ->orWhereNot('secondWhere', true)
            ->active();

        $this->assertSame('select * from "table" where ("firstWhere" = ? or not "secondWhere" = ?) and "active" = ?', $query->toSql());
        $this->assertEquals([true, true, true], $query->getBindings());
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
}
