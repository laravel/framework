<?php

namespace Illuminate\Tests\Database;

use Exception;
use Foo\Bar\EloquentModelNamespacedStub;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\UniqueConstraintViolationException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentMorphTest extends TestCase
{
    protected function tearDown(): void
    {
        Relation::morphMap([], false);

        m::close();
    }

    public function testMorphOneSetsProperConstraints()
    {
        $this->getOneRelation();
    }

    public function testMorphOneEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getOneRelation();
        $relation->getParent()->shouldReceive('getKeyName')->once()->andReturn('id');
        $relation->getParent()->shouldReceive('getKeyType')->once()->andReturn('string');
        $relation->getQuery()->shouldReceive('whereIn')->once()->with('table.morph_id', [1, 2]);
        $relation->getQuery()->shouldReceive('where')->once()->with('table.morph_type', get_class($relation->getParent()));

        $model1 = new EloquentMorphResetModelStub;
        $model1->id = 1;
        $model2 = new EloquentMorphResetModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);
    }

    /**
     * Note that the tests are the exact same for morph many because the classes share this code...
     * Will still test to be safe.
     */
    public function testMorphManySetsProperConstraints()
    {
        $this->getManyRelation();
    }

    public function testMorphManyEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getManyRelation();
        $relation->getParent()->shouldReceive('getKeyName')->once()->andReturn('id');
        $relation->getParent()->shouldReceive('getKeyType')->once()->andReturn('int');
        $relation->getQuery()->shouldReceive('whereIntegerInRaw')->once()->with('table.morph_id', [1, 2]);
        $relation->getQuery()->shouldReceive('where')->once()->with('table.morph_type', get_class($relation->getParent()));

        $model1 = new EloquentMorphResetModelStub;
        $model1->id = 1;
        $model2 = new EloquentMorphResetModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);
    }

    public function testMakeFunctionOnMorph()
    {
        $_SERVER['__eloquent.saved'] = false;
        // Doesn't matter which relation type we use since they share the code...
        $relation = $this->getOneRelation();
        $instance = m::mock(Model::class);
        $instance->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $instance->shouldReceive('setAttribute')->once()->with('morph_type', get_class($relation->getParent()));
        $instance->shouldReceive('save')->never();
        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['name' => 'taylor'])->andReturn($instance);

        $this->assertEquals($instance, $relation->make(['name' => 'taylor']));
    }

    public function testCreateFunctionOnMorph()
    {
        // Doesn't matter which relation type we use since they share the code...
        $relation = $this->getOneRelation();
        $created = m::mock(Model::class);
        $created->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $created->shouldReceive('setAttribute')->once()->with('morph_type', get_class($relation->getParent()));
        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['name' => 'taylor'])->andReturn($created);
        $created->shouldReceive('save')->once()->andReturn(true);

        $this->assertEquals($created, $relation->create(['name' => 'taylor']));
    }

    public function testFindOrNewMethodFindsModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('find')->once()->with('foo', ['*'])->andReturn($model = m::mock(Model::class));
        $relation->getRelated()->shouldReceive('newInstance')->never();
        $model->shouldReceive('setAttribute')->never();
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->findOrNew('foo'));
    }

    public function testFindOrNewMethodReturnsNewModelWithMorphKeysSet()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('find')->once()->with('foo', ['*'])->andReturn(null);
        $relation->getRelated()->shouldReceive('newInstance')->once()->with()->andReturn($model = m::mock(Model::class));
        $model->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $model->shouldReceive('setAttribute')->once()->with('morph_type', get_class($relation->getParent()));
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->findOrNew('foo'));
    }

    public function testFirstOrNewMethodFindsFirstModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn($model = m::mock(Model::class));
        $relation->getRelated()->shouldReceive('newInstance')->never();
        $model->shouldReceive('setAttribute')->never();
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->firstOrNew(['foo']));
    }

    public function testFirstOrNewMethodWithValueFindsFirstModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo' => 'bar'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn($model = m::mock(Model::class));
        $relation->getRelated()->shouldReceive('newInstance')->never();
        $model->shouldReceive('setAttribute')->never();
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->firstOrNew(['foo' => 'bar'], ['baz' => 'qux']));
    }

    public function testFirstOrNewMethodReturnsNewModelWithMorphKeysSet()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn(null);
        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['foo'])->andReturn($model = m::mock(Model::class));
        $model->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $model->shouldReceive('setAttribute')->once()->with('morph_type', get_class($relation->getParent()));
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->firstOrNew(['foo']));
    }

    public function testFirstOrNewMethodWithValuesReturnsNewModelWithMorphKeysSet()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo' => 'bar'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn(null);
        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['foo' => 'bar', 'baz' => 'qux'])->andReturn($model = m::mock(Model::class));
        $model->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $model->shouldReceive('setAttribute')->once()->with('morph_type', get_class($relation->getParent()));
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->firstOrNew(['foo' => 'bar'], ['baz' => 'qux']));
    }

    public function testFirstOrCreateMethodFindsFirstModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn($model = m::mock(Model::class));
        $relation->getRelated()->shouldReceive('newInstance')->never();
        $model->shouldReceive('setAttribute')->never();
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->firstOrCreate(['foo']));
    }

    public function testFirstOrCreateMethodWithValuesFindsFirstModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo' => 'bar'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn($model = m::mock(Model::class));
        $relation->getRelated()->shouldReceive('newInstance')->never();
        $model->shouldReceive('setAttribute')->never();
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->firstOrCreate(['foo' => 'bar'], ['baz' => 'qux']));
    }

    public function testFirstOrCreateMethodCreatesNewMorphModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn(null);
        $relation->getQuery()->shouldReceive('withSavepointIfNeeded')->once()->andReturnUsing(fn ($scope) => $scope());
        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['foo'])->andReturn($model = m::mock(Model::class));
        $model->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $model->shouldReceive('setAttribute')->once()->with('morph_type', get_class($relation->getParent()));
        $model->shouldReceive('save')->once()->andReturn(true);

        $this->assertInstanceOf(Model::class, $relation->firstOrCreate(['foo']));
    }

    public function testFirstOrCreateMethodWithValuesCreatesNewMorphModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo' => 'bar'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn(null);
        $relation->getQuery()->shouldReceive('withSavepointIfNeeded')->once()->andReturnUsing(fn ($scope) => $scope());
        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['foo' => 'bar', 'baz' => 'qux'])->andReturn($model = m::mock(Model::class));
        $model->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $model->shouldReceive('setAttribute')->once()->with('morph_type', get_class($relation->getParent()));
        $model->shouldReceive('save')->once()->andReturn(true);

        $this->assertInstanceOf(Model::class, $relation->firstOrCreate(['foo' => 'bar'], ['baz' => 'qux']));
    }

    public function testCreateOrFirstMethodFindsFirstModel()
    {
        $relation = $this->getOneRelation();

        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['foo'])->andReturn($model = m::mock(Model::class));
        $model->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $model->shouldReceive('setAttribute')->once()->with('morph_type', get_class($relation->getParent()));
        $model->shouldReceive('save')->once()->andThrow(
            new UniqueConstraintViolationException('mysql', 'example mysql', [], new Exception('SQLSTATE[23000]: Integrity constraint violation: 1062')),
        );

        $relation->getQuery()->shouldReceive('withSavepointIfNeeded')->once()->andReturnUsing(function ($scope) {
            return $scope();
        });
        $relation->getQuery()->shouldReceive('useWritePdo')->once()->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn($model = m::mock(Model::class));

        $this->assertInstanceOf(Model::class, $relation->createOrFirst(['foo']));
    }

    public function testCreateOrFirstMethodWithValuesFindsFirstModel()
    {
        $relation = $this->getOneRelation();

        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['foo' => 'bar', 'baz' => 'qux'])->andReturn($model = m::mock(Model::class));
        $model->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $model->shouldReceive('setAttribute')->once()->with('morph_type', get_class($relation->getParent()));
        $model->shouldReceive('save')->once()->andThrow(
            new UniqueConstraintViolationException('mysql', 'example mysql', [], new Exception('SQLSTATE[23000]: Integrity constraint violation: 1062')),
        );

        $relation->getQuery()->shouldReceive('withSavepointIfNeeded')->once()->andReturnUsing(function ($scope) {
            return $scope();
        });
        $relation->getQuery()->shouldReceive('useWritePdo')->once()->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo' => 'bar'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn($model = m::mock(Model::class));

        $this->assertInstanceOf(Model::class, $relation->createOrFirst(['foo' => 'bar'], ['baz' => 'qux']));
    }

    public function testCreateOrFirstMethodCreatesNewMorphModel()
    {
        $relation = $this->getOneRelation();

        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['foo'])->andReturn($model = m::mock(Model::class));
        $model->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $model->shouldReceive('setAttribute')->once()->with('morph_type', get_class($relation->getParent()));
        $model->shouldReceive('save')->once()->andReturn(true);

        $relation->getQuery()->shouldReceive('withSavepointIfNeeded')->once()->andReturnUsing(function ($scope) {
            return $scope();
        });
        $relation->getQuery()->shouldReceive('where')->never();
        $relation->getQuery()->shouldReceive('first')->never();

        $this->assertInstanceOf(Model::class, $relation->createOrFirst(['foo']));
    }

    public function testCreateOrFirstMethodWithValuesCreatesNewMorphModel()
    {
        $relation = $this->getOneRelation();

        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['foo' => 'bar', 'baz' => 'qux'])->andReturn($model = m::mock(Model::class));
        $model->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $model->shouldReceive('setAttribute')->once()->with('morph_type', get_class($relation->getParent()));
        $model->shouldReceive('save')->once()->andReturn(true);

        $relation->getQuery()->shouldReceive('withSavepointIfNeeded')->once()->andReturnUsing(function ($scope) {
            return $scope();
        });
        $relation->getQuery()->shouldReceive('where')->never();
        $relation->getQuery()->shouldReceive('first')->never();

        $this->assertInstanceOf(Model::class, $relation->createOrFirst(['foo' => 'bar'], ['baz' => 'qux']));
    }

    public function testUpdateOrCreateMethodFindsFirstModelAndUpdates()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn($model = m::mock(Model::class));
        $relation->getRelated()->shouldReceive('newInstance')->never();

        $model->wasRecentlyCreated = false;
        $model->shouldReceive('setAttribute')->never();
        $model->shouldReceive('fill')->once()->with(['bar'])->andReturn($model);
        $model->shouldReceive('save')->once();

        $this->assertInstanceOf(Model::class, $relation->updateOrCreate(['foo'], ['bar']));
    }

    public function testUpdateOrCreateMethodCreatesNewMorphModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('withSavepointIfNeeded')->once()->andReturnUsing(function ($scope) {
            return $scope();
        });
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn(null);
        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['foo', 'bar'])->andReturn($model = m::mock(Model::class));

        $model->wasRecentlyCreated = true;
        $model->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $model->shouldReceive('setAttribute')->once()->with('morph_type', get_class($relation->getParent()));
        $model->shouldReceive('save')->once()->andReturn(true);

        $this->assertInstanceOf(Model::class, $relation->updateOrCreate(['foo'], ['bar']));
    }

    public function testCreateFunctionOnNamespacedMorph()
    {
        $relation = $this->getNamespacedRelation('namespace');
        $created = m::mock(Model::class);
        $created->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $created->shouldReceive('setAttribute')->once()->with('morph_type', 'namespace');
        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['name' => 'taylor'])->andReturn($created);
        $created->shouldReceive('save')->once()->andReturn(true);

        $this->assertEquals($created, $relation->create(['name' => 'taylor']));
    }

    public function testIsNotNull()
    {
        $relation = $this->getOneRelation();

        $relation->getRelated()->shouldReceive('getTable')->never();
        $relation->getRelated()->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is(null));
    }

    public function testIsModel()
    {
        $relation = $this->getOneRelation();

        $relation->getRelated()->shouldReceive('getTable')->once()->andReturn('table');
        $relation->getRelated()->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('morph_id')->andReturn(1);
        $model->shouldReceive('getTable')->once()->andReturn('table');
        $model->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $this->assertTrue($relation->is($model));
    }

    public function testIsModelWithStringRelatedKey()
    {
        $relation = $this->getOneRelation();

        $relation->getRelated()->shouldReceive('getTable')->once()->andReturn('table');
        $relation->getRelated()->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('morph_id')->andReturn('1');
        $model->shouldReceive('getTable')->once()->andReturn('table');
        $model->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $this->assertTrue($relation->is($model));
    }

    public function testIsNotModelWithNullRelatedKey()
    {
        $relation = $this->getOneRelation();

        $relation->getRelated()->shouldReceive('getTable')->never();
        $relation->getRelated()->shouldReceive('getConnectionName')->never();

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('morph_id')->andReturn(null);
        $model->shouldReceive('getTable')->never();
        $model->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is($model));
    }

    public function testIsNotModelWithAnotherRelatedKey()
    {
        $relation = $this->getOneRelation();

        $relation->getRelated()->shouldReceive('getTable')->never();
        $relation->getRelated()->shouldReceive('getConnectionName')->never();

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('morph_id')->andReturn(2);
        $model->shouldReceive('getTable')->never();
        $model->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is($model));
    }

    public function testIsNotModelWithAnotherTable()
    {
        $relation = $this->getOneRelation();

        $relation->getRelated()->shouldReceive('getTable')->once()->andReturn('table');
        $relation->getRelated()->shouldReceive('getConnectionName')->never();

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('morph_id')->andReturn(1);
        $model->shouldReceive('getTable')->once()->andReturn('table.two');
        $model->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is($model));
    }

    public function testIsNotModelWithAnotherConnection()
    {
        $relation = $this->getOneRelation();

        $relation->getRelated()->shouldReceive('getTable')->once()->andReturn('table');
        $relation->getRelated()->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('morph_id')->andReturn(1);
        $model->shouldReceive('getTable')->once()->andReturn('table');
        $model->shouldReceive('getConnectionName')->once()->andReturn('connection.two');

        $this->assertFalse($relation->is($model));
    }

    protected function getOneRelation()
    {
        $queryBuilder = m::mock(QueryBuilder::class);
        $builder = m::mock(Builder::class, [$queryBuilder]);
        $builder->shouldReceive('whereNotNull')->once()->with('table.morph_id');
        $builder->shouldReceive('where')->once()->with('table.morph_id', '=', 1);
        $related = m::mock(Model::class);
        $builder->shouldReceive('getModel')->andReturn($related);
        $parent = m::mock(Model::class);
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $parent->shouldReceive('getMorphClass')->andReturn(get_class($parent));
        $builder->shouldReceive('where')->once()->with('table.morph_type', get_class($parent));

        return new MorphOne($builder, $parent, 'table.morph_type', 'table.morph_id', 'id');
    }

    protected function getManyRelation()
    {
        $builder = m::mock(Builder::class);
        $builder->shouldReceive('whereNotNull')->once()->with('table.morph_id');
        $builder->shouldReceive('where')->once()->with('table.morph_id', '=', 1);
        $related = m::mock(Model::class);
        $builder->shouldReceive('getModel')->andReturn($related);
        $parent = m::mock(Model::class);
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $parent->shouldReceive('getMorphClass')->andReturn(get_class($parent));
        $builder->shouldReceive('where')->once()->with('table.morph_type', get_class($parent));

        return new MorphMany($builder, $parent, 'table.morph_type', 'table.morph_id', 'id');
    }

    protected function getNamespacedRelation($alias)
    {
        require_once __DIR__.'/stubs/EloquentModelNamespacedStub.php';

        Relation::morphMap([
            $alias => EloquentModelNamespacedStub::class,
        ]);

        $builder = m::mock(Builder::class);
        $builder->shouldReceive('whereNotNull')->once()->with('table.morph_id');
        $builder->shouldReceive('where')->once()->with('table.morph_id', '=', 1);
        $related = m::mock(Model::class);
        $builder->shouldReceive('getModel')->andReturn($related);
        $parent = m::mock(EloquentModelNamespacedStub::class);
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $parent->shouldReceive('getMorphClass')->andReturn($alias);
        $builder->shouldReceive('where')->once()->with('table.morph_type', $alias);

        return new MorphOne($builder, $parent, 'table.morph_type', 'table.morph_id', 'id');
    }
}

class EloquentMorphResetModelStub extends Model
{
    //
}
