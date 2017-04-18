<?php

use Mockery as m;
use Illuminate\Database\Grammar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;

class DatabaseEloquentRelationTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testSetRelationFail()
    {
        $parent = new EloquentRelationResetModelStub;
        $relation = new EloquentRelationResetModelStub;
        $parent->setRelation('test', $relation);
        $parent->setRelation('foo', 'bar');
        $this->assertArrayNotHasKey('foo', $parent->toArray());
    }

    public function testTouchMethodUpdatesRelatedTimestamps()
    {
        $builder = m::mock(Builder::class);
        $parent = m::mock(Model::class);
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $builder->shouldReceive('getModel')->andReturn($related = m::mock(StdClass::class));
        $builder->shouldReceive('whereNotNull');
        $builder->shouldReceive('where');
        $relation = new HasOne($builder, $parent, 'foreign_key', 'id');
        $related->shouldReceive('getTable')->andReturn('table');
        $related->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
        $now = Carbon\Carbon::now();
        $related->shouldReceive('freshTimestampString')->andReturn($now);
        $builder->shouldReceive('update')->once()->with(['updated_at' => $now]);

        $relation->touch();
    }

    public function testSettingMorphMapWithNumericArrayUsesTheTableNames()
    {
        Relation::morphMap([EloquentRelationResetModelStub::class]);

        $this->assertEquals([
            'reset' => 'EloquentRelationResetModelStub',
        ], Relation::morphMap());

        Relation::morphMap([], false);
    }

    /**
     * Testing to ensure loop does not occur during relational queries in global scopes.
     *
     * Executing parent model's global scopes could result in an infinite loop when the
     * parent model's global scope utilizes a relation in a query like has or whereHas
     */
    public function testDonNotRunParentModelGlobalScopes()
    {
        /* @var Mockery\MockInterface $parent */
        $eloquentBuilder = m::mock(Builder::class);
        $queryBuilder = m::mock(QueryBuilder::class);
        $parent = m::mock(EloquentRelationResetModelStub::class)->makePartial();
        $grammar = m::mock(Grammar::class);

        $eloquentBuilder->shouldReceive('getModel')->andReturn($related = m::mock(StdClass::class));
        $eloquentBuilder->shouldReceive('getQuery')->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('getGrammar')->andReturn($grammar);
        $grammar->shouldReceive('wrap');
        $parent->shouldReceive('newQueryWithoutScopes')->andReturn($eloquentBuilder);

        $relation = new EloquentRelationStub($eloquentBuilder, $parent);
        $relation->wrap('test');
    }
}

class EloquentRelationResetModelStub extends Model
{
    protected $table = 'reset';

    // Override method call which would normally go through __call()

    public function getQuery()
    {
        return $this->newQuery()->getQuery();
    }
}

class EloquentRelationStub extends Relation
{
    public function addConstraints()
    {
    }

    public function addEagerConstraints(array $models)
    {
    }

    public function initRelation(array $models, $relation)
    {
    }

    public function match(array $models, Illuminate\Database\Eloquent\Collection $results, $relation)
    {
    }

    public function getResults()
    {
    }
}
