<?php

use Mockery as m;

class DatabaseEloquentGlobalScopesTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testGlobalScopeIsApplied()
    {
        $model = new EloquentGlobalScopesTestModel();
        $query = $model->newQuery();
        $this->assertEquals('select * from "table" where "active" = ?', $query->toSql());
        $this->assertEquals([1], $query->getBindings());
    }

    public function testGlobalScopeCanBeRemoved()
    {
        $model = new EloquentGlobalScopesTestModel();
        $query = $model->newQuery()->withoutGlobalScope(ActiveScope::class);
        $this->assertEquals('select * from "table"', $query->toSql());
        $this->assertEquals([], $query->getBindings());
    }

    public function testClosureGlobalScopeIsApplied()
    {
        $model = new EloquentClosureGlobalScopesTestModel();
        $query = $model->newQuery();
        $this->assertEquals('select * from "table" where "active" = ? order by "name" asc', $query->toSql());
        $this->assertEquals([1], $query->getBindings());
    }

    public function testClosureGlobalScopeCanBeRemoved()
    {
        $model = new EloquentClosureGlobalScopesTestModel();
        $query = $model->newQuery()->withoutGlobalScope('active_scope');
        $this->assertEquals('select * from "table" order by "name" asc', $query->toSql());
        $this->assertEquals([], $query->getBindings());
    }

    public function testGlobalScopeCanBeRemovedAfterTheQueryIsExecuted()
    {
        $model = new EloquentClosureGlobalScopesTestModel();
        $query = $model->newQuery();
        $this->assertEquals('select * from "table" where "active" = ? order by "name" asc', $query->toSql());
        $this->assertEquals([1], $query->getBindings());

        $query->withoutGlobalScope('active_scope');
        $this->assertEquals('select * from "table" order by "name" asc', $query->toSql());
        $this->assertEquals([], $query->getBindings());
    }

    public function testAllGlobalScopesCanBeRemoved()
    {
        $model = new EloquentClosureGlobalScopesTestModel();
        $query = $model->newQuery()->withoutGlobalScopes();
        $this->assertEquals('select * from "table"', $query->toSql());
        $this->assertEquals([], $query->getBindings());

        $query = EloquentClosureGlobalScopesTestModel::withoutGlobalScopes();
        $this->assertEquals('select * from "table"', $query->toSql());
        $this->assertEquals([], $query->getBindings());
    }
}

class EloquentClosureGlobalScopesTestModel extends Illuminate\Database\Eloquent\Model
{
    protected $table = 'table';

    public static function boot()
    {
        static::addGlobalScope('active_scope', function ($query) {
            $query->where('active', 1);
        });

        static::addGlobalScope(function ($query) {
            $query->orderBy('name');
        });

        parent::boot();
    }
}

class EloquentGlobalScopesTestModel extends Illuminate\Database\Eloquent\Model
{
    protected $table = 'table';

    public static function boot()
    {
        static::addGlobalScope(new ActiveScope);

        parent::boot();
    }
}

class ActiveScope implements \Illuminate\Database\Eloquent\ScopeInterface
{
    public function apply(\Illuminate\Database\Eloquent\Builder $builder, \Illuminate\Database\Eloquent\Model $model)
    {
        return $builder->where('active', 1);
    }
}
