<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DatabaseEloquentHasOneTest extends TestCase
{
    protected $builder;

    protected $related;

    protected $parent;

    public function tearDown()
    {
        m::close();
    }

    public function testHasOneWithDefault()
    {
        $relation = $this->getRelation()->withDefault();

        $this->builder->shouldReceive('first')->once()->andReturnNull();

        $newModel = new EloquentHasOneModelStub();

        $this->related->shouldReceive('newInstance')->once()->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());

        $this->assertSame(1, $newModel->getAttribute('foreign_key'));
    }

    public function testHasOneWithDynamicDefault()
    {
        $relation = $this->getRelation()->withDefault(function ($newModel) {
            $newModel->username = 'taylor';
        });

        $this->builder->shouldReceive('first')->once()->andReturnNull();

        $newModel = new EloquentHasOneModelStub();

        $this->related->shouldReceive('newInstance')->once()->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());

        $this->assertSame('taylor', $newModel->username);

        $this->assertSame(1, $newModel->getAttribute('foreign_key'));
    }

    public function testHasOneWithArrayDefault()
    {
        $attributes = ['username' => 'taylor'];

        $relation = $this->getRelation()->withDefault($attributes);

        $this->builder->shouldReceive('first')->once()->andReturnNull();

        $newModel = new EloquentHasOneModelStub();

        $this->related->shouldReceive('newInstance')->once()->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());

        $this->assertSame('taylor', $newModel->username);

        $this->assertSame(1, $newModel->getAttribute('foreign_key'));
    }

    public function testMakeMethodDoesNotSaveNewModel()
    {
        $relation = $this->getRelation();
        $instance = $this->getMockBuilder('Illuminate\Database\Eloquent\Model')->setMethods(['save', 'newInstance', 'setAttribute'])->getMock();
        $relation->getRelated()->shouldReceive('newInstance')->with(['name' => 'taylor'])->andReturn($instance);
        $instance->expects($this->once())->method('setAttribute')->with('foreign_key', 1);
        $instance->expects($this->never())->method('save');

        $this->assertEquals($instance, $relation->make(['name' => 'taylor']));
    }

    public function testSaveMethodSetsForeignKeyOnModel()
    {
        $relation = $this->getRelation();
        $mockModel = $this->getMockBuilder('Illuminate\Database\Eloquent\Model')->setMethods(['save'])->getMock();
        $mockModel->expects($this->once())->method('save')->will($this->returnValue(true));
        $result = $relation->save($mockModel);

        $attributes = $result->getAttributes();
        $this->assertEquals(1, $attributes['foreign_key']);
    }

    public function testCreateMethodProperlyCreatesNewModel()
    {
        $relation = $this->getRelation();
        $created = $this->getMockBuilder('Illuminate\Database\Eloquent\Model')->setMethods(['save', 'getKey', 'setAttribute'])->getMock();
        $created->expects($this->once())->method('save')->will($this->returnValue(true));
        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['name' => 'taylor'])->andReturn($created);
        $created->expects($this->once())->method('setAttribute')->with('foreign_key', 1);

        $this->assertEquals($created, $relation->create(['name' => 'taylor']));
    }

    public function testUpdateMethodUpdatesModelsWithTimestamps()
    {
        $relation = $this->getRelation();
        $relation->getRelated()->shouldReceive('usesTimestamps')->once()->andReturn(true);
        $relation->getRelated()->shouldReceive('freshTimestampString')->once()->andReturn(100);
        $relation->getRelated()->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
        $relation->getQuery()->shouldReceive('update')->once()->with(['foo' => 'bar', 'updated_at' => 100])->andReturn('results');

        $this->assertEquals('results', $relation->update(['foo' => 'bar']));
    }

    public function testRelationIsProperlyInitialized()
    {
        $relation = $this->getRelation();
        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $model->shouldReceive('setRelation')->once()->with('foo', null);
        $models = $relation->initRelation([$model], 'foo');

        $this->assertEquals([$model], $models);
    }

    public function testEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getRelation();
        $relation->getQuery()->shouldReceive('whereIn')->once()->with('table.foreign_key', [1, 2]);
        $model1 = new EloquentHasOneModelStub;
        $model1->id = 1;
        $model2 = new EloquentHasOneModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);
    }

    public function testModelsAreProperlyMatchedToParents()
    {
        $relation = $this->getRelation();

        $result1 = new EloquentHasOneModelStub;
        $result1->foreign_key = 1;
        $result2 = new EloquentHasOneModelStub;
        $result2->foreign_key = 2;

        $model1 = new EloquentHasOneModelStub;
        $model1->id = 1;
        $model2 = new EloquentHasOneModelStub;
        $model2->id = 2;
        $model3 = new EloquentHasOneModelStub;
        $model3->id = 3;

        $models = $relation->match([$model1, $model2, $model3], new Collection([$result1, $result2]), 'foo');

        $this->assertEquals(1, $models[0]->foo->foreign_key);
        $this->assertEquals(2, $models[1]->foo->foreign_key);
        $this->assertNull($models[2]->foo);
    }

    public function testRelationCountQueryCanBeBuilt()
    {
        $relation = $this->getRelation();
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');

        $baseQuery = m::mock('Illuminate\Database\Query\Builder');
        $baseQuery->from = 'one';
        $parentQuery = m::mock('Illuminate\Database\Query\Builder');
        $parentQuery->from = 'two';

        $builder->shouldReceive('getQuery')->once()->andReturn($baseQuery);
        $builder->shouldReceive('getQuery')->once()->andReturn($parentQuery);

        $builder->shouldReceive('select')->once()->with(m::type('Illuminate\Database\Query\Expression'))->andReturnSelf();
        $relation->getParent()->shouldReceive('getTable')->andReturn('table');
        $builder->shouldReceive('whereColumn')->once()->with('table.id', '=', 'table.foreign_key');

        $relation->getRelationExistenceCountQuery($builder, $builder);
    }

    protected function getRelation()
    {
        $this->builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $this->builder->shouldReceive('whereNotNull')->with('table.foreign_key');
        $this->builder->shouldReceive('where')->with('table.foreign_key', '=', 1);
        $this->related = m::mock('Illuminate\Database\Eloquent\Model');
        $this->builder->shouldReceive('getModel')->andReturn($this->related);
        $this->parent = m::mock('Illuminate\Database\Eloquent\Model');
        $this->parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $this->parent->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
        $this->parent->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
        $this->parent->shouldReceive('newQueryWithoutScopes')->andReturn($this->builder);

        return new HasOne($this->builder, $this->parent, 'table.foreign_key', 'id');
    }
}

class EloquentHasOneModelStub extends \Illuminate\Database\Eloquent\Model
{
    public $foreign_key = 'foreign.value';
}
