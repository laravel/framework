<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Tests\App\Enums\Bar;
use Illuminate\Tests\App\Models\Relationships\AnotherEloquentBelongsToModelStub;
use Illuminate\Tests\App\Models\Relationships\EloquentBelongsToModelStub;
use Illuminate\Tests\App\Models\Relationships\EloquentBelongsToModelStubWithBackedEnumCast;
use Illuminate\Tests\App\Models\Relationships\EloquentBelongsToModelStubWithZeroId;
use Illuminate\Tests\App\Models\Relationships\MissingEloquentBelongsToModelStub;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToTest extends TestCase
{
    protected $builder;

    protected $related;

    public function testBelongsToWithDefault()
    {
        $relation = $this->getRelation()->withDefault();

        $this->builder->expects('first')->andReturnNull();

        $newModel = new EloquentBelongsToModelStub;

        $this->related->expects('newInstance')->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());
    }

    public function testBelongsToWithDynamicDefault()
    {
        $relation = $this->getRelation()->withDefault(function ($newModel) {
            $newModel->username = 'taylor';
        });

        $this->builder->expects('first')->andReturnNull();

        $newModel = new EloquentBelongsToModelStub;

        $this->related->expects('newInstance')->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());

        $this->assertSame('taylor', $newModel->username);
    }

    public function testBelongsToWithArrayDefault()
    {
        $relation = $this->getRelation()->withDefault(['username' => 'taylor']);

        $this->builder->expects('first')->andReturnNull();

        $newModel = new EloquentBelongsToModelStub;

        $this->related->expects('newInstance')->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());

        $this->assertSame('taylor', $newModel->username);
    }

    public function testEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getRelation();
        $relation->getQuery()->expects('whereIntegerInRaw')->with('relation.id', ['foreign.value', 'foreign.value.two']);
        $models = [new EloquentBelongsToModelStub, new EloquentBelongsToModelStub, new AnotherEloquentBelongsToModelStub];
        $relation->addEagerConstraints($models);
    }

    public function testIdsInEagerConstraintsCanBeZero()
    {
        $relation = $this->getRelation();
        $relation->getQuery()->expects('whereIntegerInRaw')->with('relation.id', [0, 'foreign.value']);
        $models = [new EloquentBelongsToModelStub, new EloquentBelongsToModelStubWithZeroId];
        $relation->addEagerConstraints($models);
    }

    public function testIdsInEagerConstraintsCanBeBackedEnum()
    {
        $relation = $this->getRelation();
        $relation->getQuery()->expects('whereIntegerInRaw')->with('relation.id', [5, 'foreign.value']);
        $models = [new EloquentBelongsToModelStub, new EloquentBelongsToModelStubWithBackedEnumCast];
        $relation->addEagerConstraints($models);
    }

    public function testRelationIsProperlyInitialized()
    {
        $relation = $this->getRelation();
        $model = Mockery::mock(Model::class);
        $model->expects('setRelation')->with('foo', null);
        $models = $relation->initRelation([$model], 'foo');

        $this->assertEquals([$model], $models);
    }

    public function testModelsAreProperlyMatchedToParents()
    {
        $relation = $this->getRelation();

        $result1 = new class extends Model
        {
            protected $attributes = ['id' => 1];
        };

        $result2 = new class extends Model
        {
            protected $attributes = ['id' => 2];
        };

        $result3 = new class extends Model
        {
            protected $attributes = ['id' => 3];

            public function __toString()
            {
                return '3';
            }
        };

        $result4 = new class extends Model
        {
            protected $casts = [
                'id' => Bar::class,
            ];

            protected $attributes = ['id' => 5];
        };

        $model1 = new EloquentBelongsToModelStub;
        $model1->foreign_key = 1;
        $model2 = new EloquentBelongsToModelStub;
        $model2->foreign_key = 2;
        $model3 = new EloquentBelongsToModelStub;
        $model3->foreign_key = new class
        {
            public function __toString()
            {
                return '3';
            }
        };
        $model4 = new EloquentBelongsToModelStub;
        $model4->foreign_key = 5;
        $models = $relation->match(
            [$model1, $model2, $model3, $model4],
            new Collection([$result1, $result2, $result3, $result4]),
            'foo'
        );

        $this->assertEquals(1, $models[0]->foo->getAttribute('id'));
        $this->assertEquals(2, $models[1]->foo->getAttribute('id'));
        $this->assertSame('3', (string) $models[2]->foo->getAttribute('id'));
        $this->assertEquals(5, $models[3]->foo->getAttribute('id')->value);
    }

    public function testAssociateMethodSetsForeignKeyOnModel()
    {
        $parent = Mockery::mock(Model::class);
        $parent->expects('getAttribute')->with('foreign_key')->andReturn('foreign.value');
        $relation = $this->getRelation($parent);
        $associate = Mockery::mock(Model::class);
        $associate->expects('getAttribute')->with('id')->andReturn(1);
        $parent->expects('setAttribute')->with('foreign_key', 1);
        $parent->expects('setRelation')->with('relation', $associate);

        $relation->associate($associate);
    }

    public function testDissociateMethodUnsetsForeignKeyOnModel()
    {
        $parent = Mockery::mock(Model::class);
        $parent->expects('getAttribute')->with('foreign_key')->andReturn('foreign.value');
        $relation = $this->getRelation($parent);
        $parent->expects('setAttribute')->with('foreign_key', null);

        // Always set relation when we received Model
        $parent->expects('setRelation')->with('relation', null);

        $relation->dissociate();
    }

    public function testAssociateMethodSetsForeignKeyOnModelById()
    {
        $parent = Mockery::mock(Model::class);
        $parent->expects('getAttribute')->with('foreign_key')->andReturn('foreign.value');
        $relation = $this->getRelation($parent);
        $parent->expects('setAttribute')->with('foreign_key', 1);

        // Always unset relation when we received id, regardless of dirtiness
        $parent->shouldReceive('isDirty')->never();
        $parent->expects('unsetRelation')->with($relation->getRelationName());

        $relation->associate(1);
    }

    public function testDefaultEagerConstraintsWhenIncrementing()
    {
        $relation = $this->getRelation();
        $relation->getQuery()->expects('whereIntegerInRaw')->with('relation.id', Mockery::mustBe([]));
        $models = [new MissingEloquentBelongsToModelStub, new MissingEloquentBelongsToModelStub];
        $relation->addEagerConstraints($models);
    }

    public function testDefaultEagerConstraintsWhenIncrementingAndNonIntKeyType()
    {
        $relation = $this->getRelation(null, 'string');
        $relation->getQuery()->expects('whereIn')->with('relation.id', Mockery::mustBe([]));
        $models = [new MissingEloquentBelongsToModelStub, new MissingEloquentBelongsToModelStub];
        $relation->addEagerConstraints($models);
    }

    public function testDefaultEagerConstraintsWhenNotIncrementing()
    {
        $relation = $this->getRelation();
        $relation->getQuery()->expects('whereIntegerInRaw')->with('relation.id', Mockery::mustBe([]));
        $models = [new MissingEloquentBelongsToModelStub, new MissingEloquentBelongsToModelStub];
        $relation->addEagerConstraints($models);
    }

    public function testIsNotNull()
    {
        $relation = $this->getRelation();

        $this->related->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is(null));
    }

    public function testIsModel()
    {
        $relation = $this->getRelation();

        $this->related->expects('getConnectionName')->andReturn('relation');

        $model = Mockery::mock(Model::class);
        $model->expects('getAttribute')->with('id')->andReturn('foreign.value');
        $model->expects('getTable')->andReturn('relation');
        $model->expects('getConnectionName')->andReturn('relation');

        $this->assertTrue($relation->is($model));
    }

    public function testIsModelWithIntegerParentKey()
    {
        $parent = Mockery::mock(Model::class);

        // when addConstraints is called we need to return the foreign value
        $parent->expects('getAttribute')->with('foreign_key')->andReturn('foreign.value');
        // when getParentKey is called we want to return an integer
        $parent->expects('getAttribute')->with('foreign_key')->andReturn(1);

        $relation = $this->getRelation($parent);

        $this->related->expects('getConnectionName')->andReturn('relation');

        $model = Mockery::mock(Model::class);
        $model->expects('getAttribute')->with('id')->andReturn('1');
        $model->expects('getTable')->andReturn('relation');
        $model->expects('getConnectionName')->andReturn('relation');

        $this->assertTrue($relation->is($model));
    }

    public function testIsModelWithIntegerRelatedKey()
    {
        $parent = Mockery::mock(Model::class);

        // when addConstraints is called we need to return the foreign value
        $parent->expects('getAttribute')->with('foreign_key')->andReturn('foreign.value');
        // when getParentKey is called we want to return a string
        $parent->expects('getAttribute')->with('foreign_key')->andReturn('1');

        $relation = $this->getRelation($parent);

        $this->related->expects('getConnectionName')->andReturn('relation');

        $model = Mockery::mock(Model::class);
        $model->expects('getAttribute')->with('id')->andReturn(1);
        $model->expects('getTable')->andReturn('relation');
        $model->expects('getConnectionName')->andReturn('relation');

        $this->assertTrue($relation->is($model));
    }

    public function testIsModelWithIntegerKeys()
    {
        $parent = Mockery::mock(Model::class);

        // when addConstraints is called we need to return the foreign value
        $parent->expects('getAttribute')->with('foreign_key')->andReturn('foreign.value');
        // when getParentKey is called we want to return an integer
        $parent->expects('getAttribute')->with('foreign_key')->andReturn(1);

        $relation = $this->getRelation($parent);

        $this->related->expects('getConnectionName')->andReturn('relation');

        $model = Mockery::mock(Model::class);
        $model->expects('getAttribute')->with('id')->andReturn(1);
        $model->expects('getTable')->andReturn('relation');
        $model->expects('getConnectionName')->andReturn('relation');

        $this->assertTrue($relation->is($model));
    }

    public function testIsNotModelWithNullParentKey()
    {
        $parent = Mockery::mock(Model::class);

        // when addConstraints is called we need to return the foreign value
        $parent->expects('getAttribute')->with('foreign_key')->andReturn('foreign.value');
        // when getParentKey is called we want to return null
        $parent->expects('getAttribute')->with('foreign_key')->andReturn(null);

        $relation = $this->getRelation($parent);

        $this->related->shouldReceive('getConnectionName')->never();

        $model = Mockery::mock(Model::class);
        $model->expects('getAttribute')->with('id')->andReturn('foreign.value');
        $model->shouldReceive('getTable')->never();
        $model->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is($model));
    }

    public function testIsNotModelWithNullRelatedKey()
    {
        $relation = $this->getRelation();

        $this->related->shouldReceive('getConnectionName')->never();

        $model = Mockery::mock(Model::class);
        $model->expects('getAttribute')->with('id')->andReturn(null);
        $model->shouldReceive('getTable')->never();
        $model->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is($model));
    }

    public function testIsNotModelWithAnotherKey()
    {
        $relation = $this->getRelation();

        $this->related->shouldReceive('getConnectionName')->never();

        $model = Mockery::mock(Model::class);
        $model->expects('getAttribute')->with('id')->andReturn('foreign.value.two');
        $model->shouldReceive('getTable')->never();
        $model->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is($model));
    }

    public function testIsNotModelWithAnotherTable()
    {
        $relation = $this->getRelation();

        $this->related->shouldReceive('getConnectionName')->never();

        $model = Mockery::mock(Model::class);
        $model->expects('getAttribute')->with('id')->andReturn('foreign.value');
        $model->expects('getTable')->andReturn('table.two');
        $model->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is($model));
    }

    public function testIsNotModelWithAnotherConnection()
    {
        $relation = $this->getRelation();

        $this->related->expects('getConnectionName')->andReturn('relation');

        $model = Mockery::mock(Model::class);
        $model->expects('getAttribute')->with('id')->andReturn('foreign.value');
        $model->expects('getTable')->andReturn('relation');
        $model->expects('getConnectionName')->andReturn('relation.two');

        $this->assertFalse($relation->is($model));
    }

    protected function getRelation($parent = null, $keyType = 'int')
    {
        $this->builder = Mockery::mock(Builder::class);
        $this->builder->expects('where')->with('relation.id', '=', 'foreign.value');
        $this->related = Mockery::mock(Model::class);
        $this->related->shouldReceive('getKeyType')->andReturn($keyType);
        $this->related->shouldReceive('getKeyName')->andReturn('id');
        $this->related->shouldReceive('getTable')->andReturn('relation');
        $this->related->shouldReceive('qualifyColumn')->andReturnUsing(fn (string $column) => "relation.{$column}");
        $this->builder->expects('getModel')->andReturn($this->related);
        $parent = $parent ?: new EloquentBelongsToModelStub;

        return new BelongsTo($this->builder, $parent, 'foreign_key', 'id', 'relation');
    }
}
