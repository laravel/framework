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
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentMorphTest extends TestCase
{
    protected function tearDown(): void
    {
        Relation::morphMap([], false);
    }

    public function testMorphOneSetsProperConstraints()
    {
        $this->getOneRelation();
    }

    public function testMorphOneEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getOneRelation();
        $relation->getParent()->expects('getKeyName')->andReturn('id');
        $relation->getParent()->expects('getKeyType')->andReturn('string');
        $relation->getQuery()->expects('whereIn')->with('table.morph_id', [1, 2]);
        $relation->getQuery()->expects('where')->with('table.morph_type', get_class($relation->getParent()));

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
        $relation->getParent()->expects('getKeyName')->andReturn('id');
        $relation->getParent()->expects('getKeyType')->andReturn('int');
        $relation->getQuery()->expects('whereIntegerInRaw')->with('table.morph_id', [1, 2]);
        $relation->getQuery()->expects('where')->with('table.morph_type', get_class($relation->getParent()));

        $model1 = new EloquentMorphResetModelStub;
        $model1->id = 1;
        $model2 = new EloquentMorphResetModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);
    }

    public function testMorphRelationUpsertFillsForeignKey()
    {
        $relation = $this->getManyRelation();

        $relation->getQuery()->expects('upsert')->with(
            [
                ['email' => 'foo3', 'name' => 'bar', $relation->getForeignKeyName() => $relation->getParentKey(), $relation->getMorphType() => $relation->getMorphClass()],
            ],
            ['email'],
            ['name']
        );

        $relation->upsert(
            ['email' => 'foo3', 'name' => 'bar'],
            ['email'],
            ['name']
        );

        $relation->getQuery()->expects('upsert')->with(
            [
                ['email' => 'foo3', 'name' => 'bar', $relation->getForeignKeyName() => $relation->getParentKey(), $relation->getMorphType() => $relation->getMorphClass()],
                ['name' => 'bar2', 'email' => 'foo2', $relation->getForeignKeyName() => $relation->getParentKey(), $relation->getMorphType() => $relation->getMorphClass()],
            ],
            ['email'],
            ['name']
        );

        $relation->upsert(
            [
                ['email' => 'foo3', 'name' => 'bar'],
                ['name' => 'bar2', 'email' => 'foo2'],
            ],
            ['email'],
            ['name']
        );
    }

    public function testMakeFunctionOnMorph()
    {
        $_SERVER['__eloquent.saved'] = false;
        // Doesn't matter which relation type we use since they share the code...
        $relation = $this->getOneRelation();
        $instance = Mockery::mock(Model::class);
        $instance->expects('setAttribute')->with('morph_id', 1);
        $instance->expects('setAttribute')->with('morph_type', get_class($relation->getParent()));
        $instance->shouldReceive('save')->never();
        $relation->getRelated()->expects('newInstance')->with(['name' => 'taylor'])->andReturn($instance);

        $this->assertEquals($instance, $relation->make(['name' => 'taylor']));
    }

    public function testCreateFunctionOnMorph()
    {
        // Doesn't matter which relation type we use since they share the code...
        $relation = $this->getOneRelation();
        $created = Mockery::mock(Model::class);
        $created->expects('setAttribute')->with('morph_id', 1);
        $created->expects('setAttribute')->with('morph_type', get_class($relation->getParent()));
        $relation->getRelated()->expects('newInstance')->with(['name' => 'taylor'])->andReturn($created);
        $created->expects('save')->andReturn(true);

        $this->assertEquals($created, $relation->create(['name' => 'taylor']));
    }

    public function testFindOrNewMethodFindsModel()
    {
        $relation = $this->getOneRelation();
        $model = Mockery::mock(Model::class);
        $relation->getQuery()->expects('find')->with('foo', ['*'])->andReturn($model);
        $relation->getRelated()->shouldReceive('newInstance')->never();
        $model->shouldReceive('setAttribute')->never();
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->findOrNew('foo'));
    }

    public function testFindOrNewMethodReturnsNewModelWithMorphKeysSet()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->expects('find')->with('foo', ['*'])->andReturn(null);
        $model = Mockery::mock(Model::class);
        $relation->getRelated()->expects('newInstance')->with()->andReturn($model);
        $model->expects('setAttribute')->with('morph_id', 1);
        $model->expects('setAttribute')->with('morph_type', get_class($relation->getParent()));
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->findOrNew('foo'));
    }

    public function testFirstOrNewMethodFindsFirstModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->expects('where')->with(['foo'])->andReturn($relation->getQuery());
        $model = Mockery::mock(Model::class);
        $relation->getQuery()->expects('first')->with()->andReturn($model);
        $relation->getRelated()->shouldReceive('newInstance')->never();
        $model->shouldReceive('setAttribute')->never();
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->firstOrNew(['foo']));
    }

    public function testFirstOrNewMethodWithValueFindsFirstModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->expects('where')->with(['foo' => 'bar'])->andReturn($relation->getQuery());
        $model = Mockery::mock(Model::class);
        $relation->getQuery()->expects('first')->with()->andReturn($model);
        $relation->getRelated()->shouldReceive('newInstance')->never();
        $model->shouldReceive('setAttribute')->never();
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->firstOrNew(['foo' => 'bar'], ['baz' => 'qux']));
    }

    public function testFirstOrNewMethodReturnsNewModelWithMorphKeysSet()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->expects('where')->with(['foo'])->andReturn($relation->getQuery());
        $relation->getQuery()->expects('first')->with()->andReturn(null);
        $model = Mockery::mock(Model::class);
        $relation->getRelated()->expects('newInstance')->with(['foo'])->andReturn($model);
        $model->expects('setAttribute')->with('morph_id', 1);
        $model->expects('setAttribute')->with('morph_type', get_class($relation->getParent()));
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->firstOrNew(['foo']));
    }

    public function testFirstOrNewMethodWithValuesReturnsNewModelWithMorphKeysSet()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->expects('where')->with(['foo' => 'bar'])->andReturn($relation->getQuery());
        $relation->getQuery()->expects('first')->with()->andReturn(null);
        $model = Mockery::mock(Model::class);
        $relation->getRelated()->expects('newInstance')->with(['foo' => 'bar', 'baz' => 'qux'])->andReturn($model);
        $model->expects('setAttribute')->with('morph_id', 1);
        $model->expects('setAttribute')->with('morph_type', get_class($relation->getParent()));
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->firstOrNew(['foo' => 'bar'], ['baz' => 'qux']));
    }

    public function testFirstOrCreateMethodFindsFirstModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->expects('where')->with(['foo'])->andReturn($relation->getQuery());
        $model = Mockery::mock(Model::class);
        $relation->getQuery()->expects('first')->with()->andReturn($model);
        $relation->getRelated()->shouldReceive('newInstance')->never();
        $model->shouldReceive('setAttribute')->never();
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->firstOrCreate(['foo']));
    }

    public function testFirstOrCreateMethodWithValuesFindsFirstModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->expects('where')->with(['foo' => 'bar'])->andReturn($relation->getQuery());
        $model = Mockery::mock(Model::class);
        $relation->getQuery()->expects('first')->with()->andReturn($model);
        $relation->getRelated()->shouldReceive('newInstance')->never();
        $model->shouldReceive('setAttribute')->never();
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->firstOrCreate(['foo' => 'bar'], ['baz' => 'qux']));
    }

    public function testFirstOrCreateMethodCreatesNewMorphModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->expects('where')->with(['foo'])->andReturn($relation->getQuery());
        $relation->getQuery()->expects('first')->with()->andReturn(null);
        $relation->getQuery()->expects('withSavepointIfNeeded')->andReturnUsing(fn ($scope) => $scope());
        $model = Mockery::mock(Model::class);
        $relation->getRelated()->expects('newInstance')->with(['foo'])->andReturn($model);
        $model->expects('setAttribute')->with('morph_id', 1);
        $model->expects('setAttribute')->with('morph_type', get_class($relation->getParent()));
        $model->expects('save')->andReturn(true);

        $this->assertInstanceOf(Model::class, $relation->firstOrCreate(['foo']));
    }

    public function testFirstOrCreateMethodWithValuesCreatesNewMorphModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->expects('where')->with(['foo' => 'bar'])->andReturn($relation->getQuery());
        $relation->getQuery()->expects('first')->with()->andReturn(null);
        $relation->getQuery()->expects('withSavepointIfNeeded')->andReturnUsing(fn ($scope) => $scope());
        $model = Mockery::mock(Model::class);
        $relation->getRelated()->expects('newInstance')->with(['foo' => 'bar', 'baz' => 'qux'])->andReturn($model);
        $model->expects('setAttribute')->with('morph_id', 1);
        $model->expects('setAttribute')->with('morph_type', get_class($relation->getParent()));
        $model->expects('save')->andReturn(true);

        $this->assertInstanceOf(Model::class, $relation->firstOrCreate(['foo' => 'bar'], ['baz' => 'qux']));
    }

    public function testCreateOrFirstMethodFindsFirstModel()
    {
        $relation = $this->getOneRelation();

        $model = Mockery::mock(Model::class);
        $relation->getRelated()->expects('newInstance')->with(['foo'])->andReturn($model);
        $model->expects('setAttribute')->with('morph_id', 1);
        $model->expects('setAttribute')->with('morph_type', get_class($relation->getParent()));
        $model->expects('save')->andThrow(
            new UniqueConstraintViolationException('mysql', 'example mysql', [], new Exception('SQLSTATE[23000]: Integrity constraint violation: 1062')),
        );

        $relation->getQuery()->expects('withSavepointIfNeeded')->andReturnUsing(function ($scope) {
            return $scope();
        });
        $relation->getQuery()->expects('useWritePdo')->andReturn($relation->getQuery());
        $relation->getQuery()->expects('where')->with(['foo'])->andReturn($relation->getQuery());
        $model = Mockery::mock(Model::class);
        $relation->getQuery()->expects('first')->with()->andReturn($model);

        $this->assertInstanceOf(Model::class, $relation->createOrFirst(['foo']));
    }

    public function testCreateOrFirstMethodWithValuesFindsFirstModel()
    {
        $relation = $this->getOneRelation();

        $model = Mockery::mock(Model::class);
        $relation->getRelated()->expects('newInstance')->with(['foo' => 'bar', 'baz' => 'qux'])->andReturn($model);
        $model->expects('setAttribute')->with('morph_id', 1);
        $model->expects('setAttribute')->with('morph_type', get_class($relation->getParent()));
        $model->expects('save')->andThrow(
            new UniqueConstraintViolationException('mysql', 'example mysql', [], new Exception('SQLSTATE[23000]: Integrity constraint violation: 1062')),
        );

        $relation->getQuery()->expects('withSavepointIfNeeded')->andReturnUsing(function ($scope) {
            return $scope();
        });
        $relation->getQuery()->expects('useWritePdo')->andReturn($relation->getQuery());
        $relation->getQuery()->expects('where')->with(['foo' => 'bar'])->andReturn($relation->getQuery());
        $model = Mockery::mock(Model::class);
        $relation->getQuery()->expects('first')->with()->andReturn($model);

        $this->assertInstanceOf(Model::class, $relation->createOrFirst(['foo' => 'bar'], ['baz' => 'qux']));
    }

    public function testCreateOrFirstMethodCreatesNewMorphModel()
    {
        $relation = $this->getOneRelation();

        $model = Mockery::mock(Model::class);
        $relation->getRelated()->expects('newInstance')->with(['foo'])->andReturn($model);
        $model->expects('setAttribute')->with('morph_id', 1);
        $model->expects('setAttribute')->with('morph_type', get_class($relation->getParent()));
        $model->expects('save')->andReturn(true);

        $relation->getQuery()->expects('withSavepointIfNeeded')->andReturnUsing(function ($scope) {
            return $scope();
        });
        $relation->getQuery()->shouldReceive('where')->never();
        $relation->getQuery()->shouldReceive('first')->never();

        $this->assertInstanceOf(Model::class, $relation->createOrFirst(['foo']));
    }

    public function testCreateOrFirstMethodWithValuesCreatesNewMorphModel()
    {
        $relation = $this->getOneRelation();

        $model = Mockery::mock(Model::class);
        $relation->getRelated()->expects('newInstance')->with(['foo' => 'bar', 'baz' => 'qux'])->andReturn($model);
        $model->expects('setAttribute')->with('morph_id', 1);
        $model->expects('setAttribute')->with('morph_type', get_class($relation->getParent()));
        $model->expects('save')->andReturn(true);

        $relation->getQuery()->expects('withSavepointIfNeeded')->andReturnUsing(function ($scope) {
            return $scope();
        });
        $relation->getQuery()->shouldReceive('where')->never();
        $relation->getQuery()->shouldReceive('first')->never();

        $this->assertInstanceOf(Model::class, $relation->createOrFirst(['foo' => 'bar'], ['baz' => 'qux']));
    }

    public function testUpdateOrCreateMethodFindsFirstModelAndUpdates()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->expects('where')->with(['foo'])->andReturn($relation->getQuery());
        $model = Mockery::mock(Model::class);
        $relation->getQuery()->expects('first')->with()->andReturn($model);
        $relation->getRelated()->shouldReceive('newInstance')->never();

        $model->wasRecentlyCreated = false;
        $model->shouldReceive('setAttribute')->never();
        $model->expects('fill')->with(['bar'])->andReturn($model);
        $model->expects('save');

        $this->assertInstanceOf(Model::class, $relation->updateOrCreate(['foo'], ['bar']));
    }

    public function testUpdateOrCreateMethodCreatesNewMorphModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->expects('withSavepointIfNeeded')->andReturnUsing(function ($scope) {
            return $scope();
        });
        $relation->getQuery()->expects('where')->with(['foo'])->andReturn($relation->getQuery());
        $relation->getQuery()->expects('first')->with()->andReturn(null);
        $model = Mockery::mock(Model::class);
        $relation->getRelated()->expects('newInstance')->with(['foo', 'bar'])->andReturn($model);

        $model->wasRecentlyCreated = true;
        $model->expects('setAttribute')->with('morph_id', 1);
        $model->expects('setAttribute')->with('morph_type', get_class($relation->getParent()));
        $model->expects('save')->andReturn(true);

        $this->assertInstanceOf(Model::class, $relation->updateOrCreate(['foo'], ['bar']));
    }

    public function testCreateFunctionOnNamespacedMorph()
    {
        $relation = $this->getNamespacedRelation('namespace');
        $created = Mockery::mock(Model::class);
        $created->expects('setAttribute')->with('morph_id', 1);
        $created->expects('setAttribute')->with('morph_type', 'namespace');
        $relation->getRelated()->expects('newInstance')->with(['name' => 'taylor'])->andReturn($created);
        $created->expects('save')->andReturn(true);

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

        $relation->getRelated()->expects('getTable')->andReturn('table');
        $relation->getRelated()->expects('getConnectionName')->andReturn('connection');

        $model = Mockery::mock(Model::class);
        $model->expects('getAttribute')->with('morph_id')->andReturn(1);
        $model->expects('getTable')->andReturn('table');
        $model->expects('getConnectionName')->andReturn('connection');

        $this->assertTrue($relation->is($model));
    }

    public function testIsModelWithStringRelatedKey()
    {
        $relation = $this->getOneRelation();

        $relation->getRelated()->expects('getTable')->andReturn('table');
        $relation->getRelated()->expects('getConnectionName')->andReturn('connection');

        $model = Mockery::mock(Model::class);
        $model->expects('getAttribute')->with('morph_id')->andReturn('1');
        $model->expects('getTable')->andReturn('table');
        $model->expects('getConnectionName')->andReturn('connection');

        $this->assertTrue($relation->is($model));
    }

    public function testIsNotModelWithNullRelatedKey()
    {
        $relation = $this->getOneRelation();

        $relation->getRelated()->shouldReceive('getTable')->never();
        $relation->getRelated()->shouldReceive('getConnectionName')->never();

        $model = Mockery::mock(Model::class);
        $model->expects('getAttribute')->with('morph_id')->andReturn(null);
        $model->shouldReceive('getTable')->never();
        $model->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is($model));
    }

    public function testIsNotModelWithAnotherRelatedKey()
    {
        $relation = $this->getOneRelation();

        $relation->getRelated()->shouldReceive('getTable')->never();
        $relation->getRelated()->shouldReceive('getConnectionName')->never();

        $model = Mockery::mock(Model::class);
        $model->expects('getAttribute')->with('morph_id')->andReturn(2);
        $model->shouldReceive('getTable')->never();
        $model->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is($model));
    }

    public function testIsNotModelWithAnotherTable()
    {
        $relation = $this->getOneRelation();

        $relation->getRelated()->expects('getTable')->andReturn('table');
        $relation->getRelated()->shouldReceive('getConnectionName')->never();

        $model = Mockery::mock(Model::class);
        $model->expects('getAttribute')->with('morph_id')->andReturn(1);
        $model->expects('getTable')->andReturn('table.two');
        $model->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is($model));
    }

    public function testIsNotModelWithAnotherConnection()
    {
        $relation = $this->getOneRelation();

        $relation->getRelated()->expects('getTable')->andReturn('table');
        $relation->getRelated()->expects('getConnectionName')->andReturn('connection');

        $model = Mockery::mock(Model::class);
        $model->expects('getAttribute')->with('morph_id')->andReturn(1);
        $model->expects('getTable')->andReturn('table');
        $model->expects('getConnectionName')->andReturn('connection.two');

        $this->assertFalse($relation->is($model));
    }

    protected function getOneRelation()
    {
        $queryBuilder = Mockery::mock(QueryBuilder::class);
        $builder = Mockery::mock(Builder::class, [$queryBuilder]);
        $builder->expects('whereNotNull')->with('table.morph_id');
        $builder->expects('where')->with('table.morph_id', '=', 1);
        $related = Mockery::mock(Model::class);
        $builder->shouldReceive('getModel')->andReturn($related);
        $parent = Mockery::mock(Model::class);
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $parent->shouldReceive('getMorphClass')->andReturn(get_class($parent));
        $builder->expects('where')->with('table.morph_type', get_class($parent));

        return new MorphOne($builder, $parent, 'table.morph_type', 'table.morph_id', 'id');
    }

    protected function getManyRelation()
    {
        $builder = Mockery::mock(Builder::class);
        $builder->expects('whereNotNull')->with('table.morph_id');
        $builder->expects('where')->with('table.morph_id', '=', 1);
        $related = Mockery::mock(Model::class);
        $builder->shouldReceive('getModel')->andReturn($related);
        $parent = Mockery::mock(Model::class);
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $parent->shouldReceive('getMorphClass')->andReturn(get_class($parent));
        $builder->expects('where')->with('table.morph_type', get_class($parent));

        return new MorphMany($builder, $parent, 'table.morph_type', 'table.morph_id', 'id');
    }

    protected function getNamespacedRelation($alias)
    {
        require_once __DIR__.'/Fixtures/EloquentModelNamespacedStub.php';

        Relation::morphMap([
            $alias => EloquentModelNamespacedStub::class,
        ]);

        $builder = Mockery::mock(Builder::class);
        $builder->expects('whereNotNull')->with('table.morph_id');
        $builder->expects('where')->with('table.morph_id', '=', 1);
        $related = Mockery::mock(Model::class);
        $builder->shouldReceive('getModel')->andReturn($related);
        $parent = Mockery::mock(EloquentModelNamespacedStub::class);
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $parent->shouldReceive('getMorphClass')->andReturn($alias);
        $builder->expects('where')->with('table.morph_type', $alias);

        return new MorphOne($builder, $parent, 'table.morph_type', 'table.morph_id', 'id');
    }
}

class EloquentMorphResetModelStub extends Model
{
    //
}
