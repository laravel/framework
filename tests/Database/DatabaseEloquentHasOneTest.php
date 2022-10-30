<?php

namespace Illuminate\Tests\Database;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentHasOneTest extends TestCase
{
    protected $builder;

    protected $related;

    protected $parent;

    protected function tearDown(): void
    {
        m::close();
    }

    public function testHasOneWithDefault()
    {
        $relation = $this->getRelation()->withDefault();

        $this->builder->shouldReceive('first')->once()->andReturnNull();

        $newModel = new EloquentHasOneModelStub;

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

        $newModel = new EloquentHasOneModelStub;

        $this->related->shouldReceive('newInstance')->once()->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());

        $this->assertSame('taylor', $newModel->username);

        $this->assertSame(1, $newModel->getAttribute('foreign_key'));
    }

    public function testHasOneWithDynamicDefaultUseParentModel()
    {
        $relation = $this->getRelation()->withDefault(function ($newModel, $parentModel) {
            $newModel->username = $parentModel->username;
        });

        $this->builder->shouldReceive('first')->once()->andReturnNull();

        $newModel = new EloquentHasOneModelStub;

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

        $newModel = new EloquentHasOneModelStub;

        $this->related->shouldReceive('newInstance')->once()->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());

        $this->assertSame('taylor', $newModel->username);

        $this->assertSame(1, $newModel->getAttribute('foreign_key'));
    }

    public function testMakeMethodDoesNotSaveNewModel()
    {
        $relation = $this->getRelation();
        $instance = $this->getMockBuilder(Model::class)->onlyMethods(['save', 'newInstance', 'setAttribute'])->getMock();
        $relation->getRelated()->shouldReceive('newInstance')->with(['name' => 'taylor'])->andReturn($instance);
        $instance->expects($this->once())->method('setAttribute')->with('foreign_key', 1);
        $instance->expects($this->never())->method('save');

        $this->assertEquals($instance, $relation->make(['name' => 'taylor']));
    }

    public function testSaveMethodSetsForeignKeyOnModel()
    {
        $relation = $this->getRelation();
        $mockModel = $this->getMockBuilder(Model::class)->onlyMethods(['save'])->getMock();
        $mockModel->expects($this->once())->method('save')->willReturn(true);
        $result = $relation->save($mockModel);

        $attributes = $result->getAttributes();
        $this->assertEquals(1, $attributes['foreign_key']);
    }

    public function testCreateMethodProperlyCreatesNewModel()
    {
        $relation = $this->getRelation();
        $created = $this->getMockBuilder(Model::class)->onlyMethods(['save', 'getKey', 'setAttribute'])->getMock();
        $created->expects($this->once())->method('save')->willReturn(true);
        $relation->getRelated()->shouldReceive('newInstance')->once()->with(['name' => 'taylor'])->andReturn($created);
        $created->expects($this->once())->method('setAttribute')->with('foreign_key', 1);

        $this->assertEquals($created, $relation->create(['name' => 'taylor']));
    }

    public function testForceCreateMethodProperlyCreatesNewModel()
    {
        $relation = $this->getRelation();
        $attributes = ['name' => 'taylor', $relation->getForeignKeyName() => $relation->getParentKey()];

        $created = m::mock(Model::class);
        $created->shouldReceive('getAttribute')->with($relation->getForeignKeyName())->andReturn($relation->getParentKey());

        $relation->getRelated()->shouldReceive('forceCreate')->once()->with($attributes)->andReturn($created);

        $this->assertEquals($created, $relation->forceCreate(['name' => 'taylor']));
        $this->assertEquals(1, $created->getAttribute('foreign_key'));
    }

    public function testRelationIsProperlyInitialized()
    {
        $relation = $this->getRelation();
        $model = m::mock(Model::class);
        $model->shouldReceive('setRelation')->once()->with('foo', null);
        $models = $relation->initRelation([$model], 'foo');

        $this->assertEquals([$model], $models);
    }

    public function testEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getRelation();
        $relation->getParent()->shouldReceive('getKeyName')->once()->andReturn('id');
        $relation->getParent()->shouldReceive('getKeyType')->once()->andReturn('int');
        $relation->getQuery()->shouldReceive('whereIntegerInRaw')->once()->with('table.foreign_key', [1, 2]);
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
        $result3 = new EloquentHasOneModelStub;
        $result3->foreign_key = new class
        {
            public function __toString()
            {
                return '4';
            }
        };

        $model1 = new EloquentHasOneModelStub;
        $model1->id = 1;
        $model2 = new EloquentHasOneModelStub;
        $model2->id = 2;
        $model3 = new EloquentHasOneModelStub;
        $model3->id = 3;
        $model4 = new EloquentHasOneModelStub;
        $model4->id = 4;

        $models = $relation->match([$model1, $model2, $model3, $model4], new Collection([$result1, $result2, $result3]), 'foo');

        $this->assertEquals(1, $models[0]->foo->foreign_key);
        $this->assertEquals(2, $models[1]->foo->foreign_key);
        $this->assertNull($models[2]->foo);
        $this->assertSame('4', (string) $models[3]->foo->foreign_key);
    }

    public function testRelationCountQueryCanBeBuilt()
    {
        $relation = $this->getRelation();
        $builder = m::mock(Builder::class);

        $baseQuery = m::mock(BaseBuilder::class);
        $baseQuery->from = 'one';
        $parentQuery = m::mock(BaseBuilder::class);
        $parentQuery->from = 'two';

        $builder->shouldReceive('getQuery')->once()->andReturn($baseQuery);
        $builder->shouldReceive('getQuery')->once()->andReturn($parentQuery);

        $builder->shouldReceive('select')->once()->with(m::type(Expression::class))->andReturnSelf();
        $relation->getParent()->shouldReceive('qualifyColumn')->andReturn('table.id');
        $builder->shouldReceive('whereColumn')->once()->with('table.id', '=', 'table.foreign_key')->andReturn($baseQuery);
        $baseQuery->shouldReceive('setBindings')->once()->with([], 'select');

        $relation->getRelationExistenceCountQuery($builder, $builder);
    }

    public function testIsNotNull()
    {
        $relation = $this->getRelation();

        $this->related->shouldReceive('getTable')->never();
        $this->related->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is(null));
    }

    public function testIsModel()
    {
        $relation = $this->getRelation();

        $this->related->shouldReceive('getTable')->once()->andReturn('table');
        $this->related->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn(1);
        $model->shouldReceive('getTable')->once()->andReturn('table');
        $model->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $this->assertTrue($relation->is($model));
    }

    public function testIsModelWithStringRelatedKey()
    {
        $relation = $this->getRelation();

        $this->related->shouldReceive('getTable')->once()->andReturn('table');
        $this->related->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('1');
        $model->shouldReceive('getTable')->once()->andReturn('table');
        $model->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $this->assertTrue($relation->is($model));
    }

    public function testIsNotModelWithNullRelatedKey()
    {
        $relation = $this->getRelation();

        $this->related->shouldReceive('getTable')->never();
        $this->related->shouldReceive('getConnectionName')->never();

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn(null);
        $model->shouldReceive('getTable')->never();
        $model->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is($model));
    }

    public function testIsNotModelWithAnotherRelatedKey()
    {
        $relation = $this->getRelation();

        $this->related->shouldReceive('getTable')->never();
        $this->related->shouldReceive('getConnectionName')->never();

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn(2);
        $model->shouldReceive('getTable')->never();
        $model->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is($model));
    }

    public function testIsNotModelWithAnotherTable()
    {
        $relation = $this->getRelation();

        $this->related->shouldReceive('getTable')->once()->andReturn('table');
        $this->related->shouldReceive('getConnectionName')->never();

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn(1);
        $model->shouldReceive('getTable')->once()->andReturn('table.two');
        $model->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is($model));
    }

    public function testIsNotModelWithAnotherConnection()
    {
        $relation = $this->getRelation();

        $this->related->shouldReceive('getTable')->once()->andReturn('table');
        $this->related->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn(1);
        $model->shouldReceive('getTable')->once()->andReturn('table');
        $model->shouldReceive('getConnectionName')->once()->andReturn('connection.two');

        $this->assertFalse($relation->is($model));
    }

    protected function getRelation()
    {
        $this->builder = m::mock(Builder::class);
        $this->builder->shouldReceive('whereNotNull')->with('table.foreign_key');
        $this->builder->shouldReceive('where')->with('table.foreign_key', '=', 1);
        $this->related = m::mock(Model::class);
        $this->builder->shouldReceive('getModel')->andReturn($this->related);
        $this->parent = m::mock(Model::class);
        $this->parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $this->parent->shouldReceive('getAttribute')->with('username')->andReturn('taylor');
        $this->parent->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
        $this->parent->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
        $this->parent->shouldReceive('newQueryWithoutScopes')->andReturn($this->builder);

        return new HasOne($this->builder, $this->parent, 'table.foreign_key', 'id');
    }
}

class EloquentHasOneModelStub extends Model
{
    public $foreign_key = 'foreign.value';
}
