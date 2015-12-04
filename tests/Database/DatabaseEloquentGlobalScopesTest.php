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

    public function testGlobalScopesWithOrWhereConditionsAreNested()
    {
        $model = new EloquentClosureGlobalScopesWithOrTestModel();

        $query = $model->newQuery()->where('col1', 'val1')->orWhere('col2', 'val2');
        $this->assertEquals('select "email", "password" from "table" where ("col1" = ? or "col2" = ?) and ("email" = ? or "email" = ?) and ("active" = ?) order by "name" asc', $query->toSql());
        $this->assertEquals(['val1', 'val2', 'taylor@gmail.com', 'someone@else.com', 1], $query->getBindings());
    }

    public function testRegularScopesWithOrWhereConditionsAreNested()
    {
        $query = EloquentClosureGlobalScopesTestModel::withoutGlobalScopes()->where('foo', 'foo')->orWhere('bar', 'bar')->approved();

        $this->assertEquals('select * from "table" where ("foo" = ? or "bar" = ?) and ("approved" = ? or "should_approve" = ?)', $query->toSql());
        $this->assertEquals(['foo', 'bar', 1, 0], $query->getBindings());
    }
}

class EloquentClosureGlobalScopesTestModel extends Illuminate\Database\Eloquent\Model
{
    protected $table = 'table';

    public static function boot()
    {
        static::addGlobalScope(function ($query) {
            $query->orderBy('name');
        });

        static::addGlobalScope('active_scope', function ($query) {
            $query->where('active', 1);
        });

        parent::boot();
    }

    public function scopeApproved($query)
    {
        return $query->where('approved', 1)->orWhere('should_approve', 0);
    }
}

class EloquentClosureGlobalScopesWithOrTestModel extends EloquentClosureGlobalScopesTestModel
{
    public static function boot()
    {
        static::addGlobalScope('or_scope', function ($query) {
            $query->where('email', 'taylor@gmail.com')->orWhere('email', 'someone@else.com');
        });

        static::addGlobalScope(function ($query) {
            $query->select('email', 'password');
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
