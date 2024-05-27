<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Concerns\SupportsInverseRelations;
use Illuminate\Database\Eloquent\Relations\Relation;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentInverseRelationTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBuilderCallbackIsNotAppliedWhenInverseRelationIsNotSet()
    {
        $builder = m::mock(Builder::class);
        $builder->shouldReceive('getModel')->andReturn(new HasInverseRelationRelatedStub());
        $builder->shouldReceive('afterQuery')->never();

        new HasInverseRelationStub($builder, new HasInverseRelationParentStub());
    }

    public function testInverseRelationCallbackIsNotSetIfInverseRelationIsEmpty()
    {
        $builder = m::mock(Builder::class);

        $this->expectException(RelationNotFoundException::class);
        $builder->shouldReceive('getModel')->andReturn(new HasInverseRelationRelatedStub());
        $builder->shouldReceive('afterQuery')->never();

        (new HasInverseRelationStub($builder, new HasInverseRelationParentStub()))->inverse('');
    }

    public function testInverseRelationCallbackIsNotSetIfInverseRelationshipDoesNotExist()
    {
        $builder = m::mock(Builder::class);

        $this->expectException(RelationNotFoundException::class);
        $builder->shouldReceive('getModel')->andReturn(new HasInverseRelationRelatedStub());
        $builder->shouldReceive('afterQuery')->never();

        (new HasInverseRelationStub($builder, new HasInverseRelationParentStub()))->inverse('foo');
    }

    public function testWithoutInverseMethodRemovesInverseRelation()
    {
        $builder = m::mock(Builder::class);

        $builder->shouldReceive('getModel')->andReturn(new HasInverseRelationRelatedStub());
        $builder->shouldReceive('afterQuery')->once()->andReturnSelf();

        $relation = (new HasInverseRelationStub($builder, new HasInverseRelationParentStub()));
        $this->assertNull($relation->getInverseRelationship());

        $relation->inverse('test');
        $this->assertSame('test', $relation->getInverseRelationship());

        $relation->withoutInverse();
        $this->assertNull($relation->getInverseRelationship());
    }

    public function testBuilderCallbackIsAppliedWhenInverseRelationIsSet()
    {
        $builder = m::mock(Builder::class);
        $builder->shouldReceive('getModel')->andReturn(new HasInverseRelationRelatedStub());

        $parent = new HasInverseRelationParentStub();
        $builder->shouldReceive('afterQuery')->withArgs(function (\Closure $callback) use ($parent) {
            $relation = (new \ReflectionFunction($callback))->getClosureThis();

            return $relation instanceof HasInverseRelationStub && $relation->getParent() === $parent;
        })->once()->andReturnSelf();

        (new HasInverseRelationStub($builder, $parent))->inverse('test');
    }

    public function testBuilderCallbackAppliesInverseRelationToAllModelsInResult()
    {
        $builder = m::mock(Builder::class);
        $builder->shouldReceive('getModel')->andReturn(new HasInverseRelationRelatedStub());

        // Capture the callback so that we can manually call it.
        $afterQuery = null;
        $builder->shouldReceive('afterQuery')->withArgs(function (\Closure $callback) use (&$afterQuery) {
            return (bool) $afterQuery = $callback;
        })->once()->andReturnSelf();

        $parent = new HasInverseRelationParentStub();
        (new HasInverseRelationStub($builder, $parent))->inverse('test');

        $results = new Collection(array_fill(0, 5, new HasInverseRelationRelatedStub()));

        foreach ($results as $model) {
            $this->assertEmpty($model->getRelations());
            $this->assertFalse($model->relationLoaded('test'));
        }

        $results = $afterQuery($results);

        foreach ($results as $model) {
            $this->assertNotEmpty($model->getRelations());
            $this->assertTrue($model->relationLoaded('test'));
            $this->assertSame($parent, $model->test);
        }
    }

    public function testInverseRelationIsNotSetIfInverseRelationIsUnset()
    {
        $builder = m::mock(Builder::class);
        $builder->shouldReceive('getModel')->andReturn(new HasInverseRelationRelatedStub());

        // Capture the callback so that we can manually call it.
        $afterQuery = null;
        $builder->shouldReceive('afterQuery')->withArgs(function (\Closure $callback) use (&$afterQuery) {
            return (bool) $afterQuery = $callback;
        })->once()->andReturnSelf();

        $parent = new HasInverseRelationParentStub();
        $relation = (new HasInverseRelationStub($builder, $parent));
        $relation->inverse('test');

        $results = new Collection(array_fill(0, 5, new HasInverseRelationRelatedStub()));
        foreach ($results as $model) {
            $this->assertEmpty($model->getRelations());
        }
        $results = $afterQuery($results);
        foreach ($results as $model) {
            $this->assertNotEmpty($model->getRelations());
            $this->assertSame($parent, $model->getRelation('test'));
        }

        // Reset the inverse relation
        $relation->withoutInverse();

        $results = new Collection(array_fill(0, 5, new HasInverseRelationRelatedStub()));
        foreach ($results as $model) {
            $this->assertEmpty($model->getRelations());
        }
        foreach ($results as $model) {
            $this->assertEmpty($model->getRelations());
        }
    }
}

class HasInverseRelationParentStub extends Model
{
    protected static $unguarded = true;
}

class HasInverseRelationRelatedStub extends Model
{
    protected static $unguarded = true;

    public function test(): BelongsTo
    {
        return $this->belongsTo(HasInverseRelationParentStub::class);
    }
}

class HasInverseRelationStub extends Relation
{
    use SupportsInverseRelations;

    // None of these methods will actually be called - they're just needed to fill out `Relation`
    public function match(array $models, Collection $results, $relation)
    {
        return $models;
    }

    public function initRelation(array $models, $relation)
    {
        return $models;
    }

    public function getResults()
    {
        return $this->query->get();
    }

    public function addConstraints()
    {
        //
    }

    public function addEagerConstraints(array $models)
    {
        //
    }
}
