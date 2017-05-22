<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DatabaseEloquentMorphTest extends TestCase
{
    public function tearDown()
    {
        Relation::morphMap([], false);

        m::close();
    }

    public function testMorphOneSetsProperConstraints()
    {
        $relation = $this->getOneRelation();
    }

    public function testMorphOneEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getOneRelation();
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
        $relation = $this->getManyRelation();
    }

    public function testMorphManyEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getManyRelation();
        $relation->getQuery()->shouldReceive('whereIn')->once()->with('table.morph_id', [1, 2]);
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
        $instance = m::mock('Illuminate\Database\Eloquent\Model');
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
        $created = m::mock('Illuminate\Database\Eloquent\Model');
        $created->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $created->shouldReceive('setAttribute')->once()->with('morph_type', get_class($relation->getParent()));
        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['name' => 'taylor'])->andReturn($created);
        $created->shouldReceive('save')->once()->andReturn(true);

        $this->assertEquals($created, $relation->create(['name' => 'taylor']));
    }

    public function testFindOrNewMethodFindsModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('find')->once()->with('foo', ['*'])->andReturn($model = m::mock('Illuminate\Database\Eloquent\Model'));
        $relation->getRelated()->shouldReceive('newInstance')->never();
        $model->shouldReceive('setAttribute')->never();
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->findOrNew('foo'));
    }

    public function testFindOrNewMethodReturnsNewModelWithMorphKeysSet()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('find')->once()->with('foo', ['*'])->andReturn(null);
        $relation->getRelated()->shouldReceive('newInstance')->once()->with()->andReturn($model = m::mock('Illuminate\Database\Eloquent\Model'));
        $model->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $model->shouldReceive('setAttribute')->once()->with('morph_type', get_class($relation->getParent()));
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->findOrNew('foo'));
    }

    public function testFirstOrNewMethodFindsFirstModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn($model = m::mock('Illuminate\Database\Eloquent\Model'));
        $relation->getRelated()->shouldReceive('newInstance')->never();
        $model->shouldReceive('setAttribute')->never();
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->firstOrNew(['foo']));
    }

    public function testFirstOrNewMethodReturnsNewModelWithMorphKeysSet()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn(null);
        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['foo'])->andReturn($model = m::mock('Illuminate\Database\Eloquent\Model'));
        $model->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $model->shouldReceive('setAttribute')->once()->with('morph_type', get_class($relation->getParent()));
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->firstOrNew(['foo']));
    }

    public function testFirstOrCreateMethodFindsFirstModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn($model = m::mock('Illuminate\Database\Eloquent\Model'));
        $relation->getRelated()->shouldReceive('newInstance')->never();
        $model->shouldReceive('setAttribute')->never();
        $model->shouldReceive('save')->never();

        $this->assertInstanceOf(Model::class, $relation->firstOrCreate(['foo']));
    }

    public function testFirstOrCreateMethodCreatesNewMorphModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn(null);
        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['foo'])->andReturn($model = m::mock('Illuminate\Database\Eloquent\Model'));
        $model->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $model->shouldReceive('setAttribute')->once()->with('morph_type', get_class($relation->getParent()));
        $model->shouldReceive('save')->once()->andReturn(true);

        $this->assertInstanceOf(Model::class, $relation->firstOrCreate(['foo']));
    }

    public function testUpdateOrCreateMethodFindsFirstModelAndUpdates()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn($model = m::mock('Illuminate\Database\Eloquent\Model'));
        $relation->getRelated()->shouldReceive('newInstance')->never();
        $model->shouldReceive('setAttribute')->never();
        $model->shouldReceive('fill')->once()->with(['bar']);
        $model->shouldReceive('save')->once();

        $this->assertInstanceOf(Model::class, $relation->updateOrCreate(['foo'], ['bar']));
    }

    public function testUpdateOrCreateMethodCreatesNewMorphModel()
    {
        $relation = $this->getOneRelation();
        $relation->getQuery()->shouldReceive('where')->once()->with(['foo'])->andReturn($relation->getQuery());
        $relation->getQuery()->shouldReceive('first')->once()->with()->andReturn(null);
        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['foo'])->andReturn($model = m::mock('Illuminate\Database\Eloquent\Model'));
        $model->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $model->shouldReceive('setAttribute')->once()->with('morph_type', get_class($relation->getParent()));
        $model->shouldReceive('save')->once()->andReturn(true);
        $model->shouldReceive('fill')->once()->with(['bar']);

        $this->assertInstanceOf(Model::class, $relation->updateOrCreate(['foo'], ['bar']));
    }

    public function testCreateFunctionOnNamespacedMorph()
    {
        $relation = $this->getNamespacedRelation('namespace');
        $created = m::mock('Illuminate\Database\Eloquent\Model');
        $created->shouldReceive('setAttribute')->once()->with('morph_id', 1);
        $created->shouldReceive('setAttribute')->once()->with('morph_type', 'namespace');
        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['name' => 'taylor'])->andReturn($created);
        $created->shouldReceive('save')->once()->andReturn(true);

        $this->assertEquals($created, $relation->create(['name' => 'taylor']));
    }

    protected function getOneRelation()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('whereNotNull')->once()->with('table.morph_id');
        $builder->shouldReceive('where')->once()->with('table.morph_id', '=', 1);
        $related = m::mock('Illuminate\Database\Eloquent\Model');
        $builder->shouldReceive('getModel')->andReturn($related);
        $parent = m::mock('Illuminate\Database\Eloquent\Model');
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $parent->shouldReceive('getMorphClass')->andReturn(get_class($parent));
        $builder->shouldReceive('where')->once()->with('table.morph_type', get_class($parent));

        return new MorphOne($builder, $parent, 'table.morph_type', 'table.morph_id', 'id');
    }

    protected function getManyRelation()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('whereNotNull')->once()->with('table.morph_id');
        $builder->shouldReceive('where')->once()->with('table.morph_id', '=', 1);
        $related = m::mock('Illuminate\Database\Eloquent\Model');
        $builder->shouldReceive('getModel')->andReturn($related);
        $parent = m::mock('Illuminate\Database\Eloquent\Model');
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $parent->shouldReceive('getMorphClass')->andReturn(get_class($parent));
        $builder->shouldReceive('where')->once()->with('table.morph_type', get_class($parent));

        return new MorphMany($builder, $parent, 'table.morph_type', 'table.morph_id', 'id');
    }

    protected function getNamespacedRelation($alias)
    {
        require_once __DIR__.'/stubs/EloquentModelNamespacedStub.php';

        Relation::morphMap([
            $alias => 'Foo\Bar\EloquentModelNamespacedStub',
        ]);

        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('whereNotNull')->once()->with('table.morph_id');
        $builder->shouldReceive('where')->once()->with('table.morph_id', '=', 1);
        $related = m::mock('Illuminate\Database\Eloquent\Model');
        $builder->shouldReceive('getModel')->andReturn($related);
        $parent = m::mock('Foo\Bar\EloquentModelNamespacedStub');
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $parent->shouldReceive('getMorphClass')->andReturn($alias);
        $builder->shouldReceive('where')->once()->with('table.morph_type', $alias);

        return new MorphOne($builder, $parent, 'table.morph_type', 'table.morph_id', 'id');
    }
}

class EloquentMorphResetModelStub extends \Illuminate\Database\Eloquent\Model
{
}
