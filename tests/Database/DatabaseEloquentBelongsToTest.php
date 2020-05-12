<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class DatabaseEloquentBelongsToTest extends TestCase
{
    protected $builder;

    protected $related;

    protected function tearDown(): void
    {
        m::close();
    }

    public function testBelongsToWithDefault()
    {
        $relation = $this->getRelation()->withDefault();

        $this->builder->shouldReceive('first')->once()->andReturnNull();

        $newModel = new EloquentBelongsToModelStub;

        $this->related->shouldReceive('newInstance')->once()->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());
    }

    public function testBelongsToWithDynamicDefault()
    {
        $relation = $this->getRelation()->withDefault(function ($newModel) {
            $newModel->username = 'taylor';
        });

        $this->builder->shouldReceive('first')->once()->andReturnNull();

        $newModel = new EloquentBelongsToModelStub;

        $this->related->shouldReceive('newInstance')->once()->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());

        $this->assertSame('taylor', $newModel->username);
    }

    public function testBelongsToWithArrayDefault()
    {
        $relation = $this->getRelation()->withDefault(['username' => 'taylor']);

        $this->builder->shouldReceive('first')->once()->andReturnNull();

        $newModel = new EloquentBelongsToModelStub;

        $this->related->shouldReceive('newInstance')->once()->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());

        $this->assertSame('taylor', $newModel->username);
    }

    public function testEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getRelation();
        $relation->getRelated()->shouldReceive('getKeyName')->andReturn('id');
        $relation->getRelated()->shouldReceive('getKeyType')->andReturn('int');
        $relation->getQuery()->shouldReceive('whereIntegerInRaw')->once()->with('relation.id', ['foreign.value', 'foreign.value.two']);
        $models = [new EloquentBelongsToModelStub, new EloquentBelongsToModelStub, new AnotherEloquentBelongsToModelStub];
        $relation->addEagerConstraints($models);
    }

    public function testIdsInEagerConstraintsCanBeZero()
    {
        $relation = $this->getRelation();
        $relation->getRelated()->shouldReceive('getKeyName')->andReturn('id');
        $relation->getRelated()->shouldReceive('getKeyType')->andReturn('int');
        $relation->getQuery()->shouldReceive('whereIntegerInRaw')->once()->with('relation.id', ['foreign.value', 0]);
        $models = [new EloquentBelongsToModelStub, new EloquentBelongsToModelStubWithZeroId];
        $relation->addEagerConstraints($models);
    }

    public function testRelationIsProperlyInitialized()
    {
        $relation = $this->getRelation();
        $model = m::mock(Model::class);
        $model->shouldReceive('setRelation')->once()->with('foo', null);
        $models = $relation->initRelation([$model], 'foo');

        $this->assertEquals([$model], $models);
    }

    public function testModelsAreProperlyMatchedToParents()
    {
        $relation = $this->getRelation();
        $result1 = m::mock(stdClass::class);
        $result1->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $result2 = m::mock(stdClass::class);
        $result2->shouldReceive('getAttribute')->with('id')->andReturn(2);
        $model1 = new EloquentBelongsToModelStub;
        $model1->foreign_key = 1;
        $model2 = new EloquentBelongsToModelStub;
        $model2->foreign_key = 2;
        $models = $relation->match([$model1, $model2], new Collection([$result1, $result2]), 'foo');

        $this->assertEquals(1, $models[0]->foo->getAttribute('id'));
        $this->assertEquals(2, $models[1]->foo->getAttribute('id'));
    }

    public function testAssociateMethodSetsForeignKeyOnModel()
    {
        $parent = m::mock(Model::class);
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');
        $relation = $this->getRelation($parent);
        $associate = m::mock(Model::class);
        $associate->shouldReceive('getAttribute')->once()->with('id')->andReturn(1);
        $parent->shouldReceive('setAttribute')->once()->with('foreign_key', 1);
        $parent->shouldReceive('setRelation')->once()->with('relation', $associate);

        $relation->associate($associate);
    }

    public function testDissociateMethodUnsetsForeignKeyOnModel()
    {
        $parent = m::mock(Model::class);
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');
        $relation = $this->getRelation($parent);
        $parent->shouldReceive('setAttribute')->once()->with('foreign_key', null);

        // Always set relation when we received Model
        $parent->shouldReceive('setRelation')->once()->with('relation', null);

        $relation->dissociate();
    }

    public function testAssociateMethodSetsForeignKeyOnModelById()
    {
        $parent = m::mock(Model::class);
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');
        $relation = $this->getRelation($parent);
        $parent->shouldReceive('setAttribute')->once()->with('foreign_key', 1);

        // Always unset relation when we received id, regardless of dirtiness
        $parent->shouldReceive('isDirty')->never();
        $parent->shouldReceive('unsetRelation')->once()->with($relation->getRelationName());

        $relation->associate(1);
    }

    public function testDefaultEagerConstraintsWhenIncrementing()
    {
        $relation = $this->getRelation();
        $relation->getRelated()->shouldReceive('getKeyName')->andReturn('id');
        $relation->getRelated()->shouldReceive('getKeyType')->andReturn('int');
        $relation->getQuery()->shouldReceive('whereIntegerInRaw')->once()->with('relation.id', m::mustBe([]));
        $models = [new MissingEloquentBelongsToModelStub, new MissingEloquentBelongsToModelStub];
        $relation->addEagerConstraints($models);
    }

    public function testDefaultEagerConstraintsWhenIncrementingAndNonIntKeyType()
    {
        $relation = $this->getRelation(null, 'string');
        $relation->getQuery()->shouldReceive('whereIn')->once()->with('relation.id', m::mustBe([]));
        $models = [new MissingEloquentBelongsToModelStub, new MissingEloquentBelongsToModelStub];
        $relation->addEagerConstraints($models);
    }

    public function testDefaultEagerConstraintsWhenNotIncrementing()
    {
        $relation = $this->getRelation();
        $relation->getRelated()->shouldReceive('getKeyName')->andReturn('id');
        $relation->getRelated()->shouldReceive('getKeyType')->andReturn('int');
        $relation->getQuery()->shouldReceive('whereIntegerInRaw')->once()->with('relation.id', m::mustBe([]));
        $models = [new MissingEloquentBelongsToModelStub, new MissingEloquentBelongsToModelStub];
        $relation->addEagerConstraints($models);
    }

    protected function getRelation($parent = null, $keyType = 'int')
    {
        $this->builder = m::mock(Builder::class);
        $this->builder->shouldReceive('where')->with('relation.id', '=', 'foreign.value');
        $this->related = m::mock(Model::class);
        $this->related->shouldReceive('getKeyType')->andReturn($keyType);
        $this->related->shouldReceive('getKeyName')->andReturn('id');
        $this->related->shouldReceive('getTable')->andReturn('relation');
        $this->builder->shouldReceive('getModel')->andReturn($this->related);
        $parent = $parent ?: new EloquentBelongsToModelStub;

        return new BelongsTo($this->builder, $parent, 'foreign_key', 'id', 'relation');
    }
}

class EloquentBelongsToModelStub extends Model
{
    public $foreign_key = 'foreign.value';
}

class AnotherEloquentBelongsToModelStub extends Model
{
    public $foreign_key = 'foreign.value.two';
}

class EloquentBelongsToModelStubWithZeroId extends Model
{
    public $foreign_key = 0;
}

class MissingEloquentBelongsToModelStub extends Model
{
    public $foreign_key;
}
