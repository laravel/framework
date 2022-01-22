<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Tests\Database\stubs\TestEnum;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentMorphToTest extends TestCase
{
    protected $builder;

    protected $related;

    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testLookupDictionaryIsProperlyConstructedForEnums()
    {
        $relation = $this->getRelation();
        $relation->addEagerConstraints([
            $one = (object) ['morph_type' => 'morph_type_2', 'foreign_key' => TestEnum::test],
        ]);
        $dictionary = $relation->getDictionary();
        $relation->getDictionary();
        $enumKey = TestEnum::test;
        if (isset($enumKey->value)) {
            $value = $dictionary['morph_type_2'][$enumKey->value][0]->foreign_key;
            $this->assertEquals(TestEnum::test, $value);
        } else {
            $this->fail('An enum should contain value property');
        }
    }

    public function testLookupDictionaryIsProperlyConstructed()
    {
        $stringish = new class
        {
            public function __toString()
            {
                return 'foreign_key_2';
            }
        };

        $relation = $this->getRelation();
        $relation->addEagerConstraints([
            $one = (object) ['morph_type' => 'morph_type_1', 'foreign_key' => 'foreign_key_1'],
            $two = (object) ['morph_type' => 'morph_type_1', 'foreign_key' => 'foreign_key_1'],
            $three = (object) ['morph_type' => 'morph_type_2', 'foreign_key' => 'foreign_key_2'],
            $four = (object) ['morph_type' => 'morph_type_2', 'foreign_key' => $stringish],
        ]);

        $dictionary = $relation->getDictionary();

        $this->assertEquals([
            'morph_type_1' => [
                'foreign_key_1' => [
                    $one,
                    $two,
                ],
            ],
            'morph_type_2' => [
                'foreign_key_2' => [
                    $three,
                    $four,
                ],
            ],
        ], $dictionary);
    }

    public function testMorphToWithDefault()
    {
        $relation = $this->getRelation()->withDefault();

        $this->builder->shouldReceive('first')->once()->andReturnNull();

        $newModel = new EloquentMorphToModelStub;

        $this->assertEquals($newModel, $relation->getResults());
    }

    public function testMorphToWithDynamicDefault()
    {
        $relation = $this->getRelation()->withDefault(function ($newModel) {
            $newModel->username = 'taylor';
        });

        $this->builder->shouldReceive('first')->once()->andReturnNull();

        $newModel = new EloquentMorphToModelStub;
        $newModel->username = 'taylor';

        $result = $relation->getResults();

        $this->assertEquals($newModel, $result);

        $this->assertSame('taylor', $result->username);
    }

    public function testMorphToWithArrayDefault()
    {
        $relation = $this->getRelation()->withDefault(['username' => 'taylor']);

        $this->builder->shouldReceive('first')->once()->andReturnNull();

        $newModel = new EloquentMorphToModelStub;
        $newModel->username = 'taylor';

        $result = $relation->getResults();

        $this->assertEquals($newModel, $result);

        $this->assertSame('taylor', $result->username);
    }

    public function testMorphToWithZeroMorphType()
    {
        $parent = $this->getMockBuilder(EloquentMorphToModelStub::class)->onlyMethods(['getAttributeFromArray', 'morphEagerTo', 'morphInstanceTo'])->getMock();
        $parent->method('getAttributeFromArray')->with('relation_type')->willReturn(0);
        $parent->expects($this->once())->method('morphInstanceTo');
        $parent->expects($this->never())->method('morphEagerTo');

        $parent->relation();
    }

    public function testMorphToWithEmptyStringMorphType()
    {
        $parent = $this->getMockBuilder(EloquentMorphToModelStub::class)->onlyMethods(['getAttributeFromArray', 'morphEagerTo', 'morphInstanceTo'])->getMock();
        $parent->method('getAttributeFromArray')->with('relation_type')->willReturn('');
        $parent->expects($this->once())->method('morphEagerTo');
        $parent->expects($this->never())->method('morphInstanceTo');

        $parent->relation();
    }

    public function testMorphToWithSpecifiedClassDefault()
    {
        $parent = new EloquentMorphToModelStub;
        $parent->relation_type = EloquentMorphToRelatedStub::class;

        $relation = $parent->relation()->withDefault();

        $newModel = new EloquentMorphToRelatedStub;

        $result = $relation->getResults();

        $this->assertEquals($newModel, $result);
    }

    public function testAssociateMethodSetsForeignKeyAndTypeOnModel()
    {
        $parent = m::mock(Model::class);
        $parent->shouldReceive('getAttribute')->with('foreign_key')->andReturn('foreign.value');

        $relation = $this->getRelationAssociate($parent);

        $associate = m::mock(Model::class);
        $associate->shouldReceive('getAttribute')->andReturn(1);
        $associate->shouldReceive('getMorphClass')->andReturn('Model');

        $parent->shouldReceive('setAttribute')->once()->with('foreign_key', 1);
        $parent->shouldReceive('setAttribute')->once()->with('morph_type', 'Model');
        $parent->shouldReceive('setRelation')->once()->with('relation', $associate);

        $relation->associate($associate);
    }

    public function testAssociateMethodIgnoresNullValue()
    {
        $parent = m::mock(Model::class);
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');

        $relation = $this->getRelationAssociate($parent);

        $parent->shouldReceive('setAttribute')->once()->with('foreign_key', null);
        $parent->shouldReceive('setAttribute')->once()->with('morph_type', null);
        $parent->shouldReceive('setRelation')->once()->with('relation', null);

        $relation->associate(null);
    }

    public function testDissociateMethodDeletesUnsetsKeyAndTypeOnModel()
    {
        $parent = m::mock(Model::class);
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');

        $relation = $this->getRelation($parent);

        $parent->shouldReceive('setAttribute')->once()->with('foreign_key', null);
        $parent->shouldReceive('setAttribute')->once()->with('morph_type', null);
        $parent->shouldReceive('setRelation')->once()->with('relation', null);

        $relation->dissociate();
    }

    public function testIsNotNull()
    {
        $relation = $this->getRelation();

        $relation->getRelated()->shouldReceive('getTable')->never();
        $relation->getRelated()->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is(null));
    }

    public function testIsModel()
    {
        $relation = $this->getRelation();

        $this->related->shouldReceive('getConnectionName')->once()->andReturn('relation');

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('id')->andReturn('foreign.value');
        $model->shouldReceive('getTable')->once()->andReturn('relation');
        $model->shouldReceive('getConnectionName')->once()->andReturn('relation');

        $this->assertTrue($relation->is($model));
    }

    public function testIsModelWithIntegerParentKey()
    {
        $parent = m::mock(Model::class);
        // when addConstraints is called we need to return the foreign value
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');
        // when getParentKey is called we want to return an integer
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn(1);

        $relation = $this->getRelation($parent);

        $this->related->shouldReceive('getConnectionName')->once()->andReturn('relation');

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('id')->andReturn('1');
        $model->shouldReceive('getTable')->once()->andReturn('relation');
        $model->shouldReceive('getConnectionName')->once()->andReturn('relation');

        $this->assertTrue($relation->is($model));
    }

    public function testIsModelWithIntegerRelatedKey()
    {
        $parent = m::mock(Model::class);
        // when addConstraints is called we need to return the foreign value
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');
        // when getParentKey is called we want to return a string
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('1');

        $relation = $this->getRelation($parent);

        $this->related->shouldReceive('getConnectionName')->once()->andReturn('relation');

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('id')->andReturn(1);
        $model->shouldReceive('getTable')->once()->andReturn('relation');
        $model->shouldReceive('getConnectionName')->once()->andReturn('relation');

        $this->assertTrue($relation->is($model));
    }

    public function testIsModelWithIntegerKeys()
    {
        $parent = m::mock(Model::class);

        // when addConstraints is called we need to return the foreign value
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');
        // when getParentKey is called we want to return an integer
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn(1);

        $relation = $this->getRelation($parent);

        $this->related->shouldReceive('getConnectionName')->once()->andReturn('relation');

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('id')->andReturn(1);
        $model->shouldReceive('getTable')->once()->andReturn('relation');
        $model->shouldReceive('getConnectionName')->once()->andReturn('relation');

        $this->assertTrue($relation->is($model));
    }

    public function testIsNotModelWithNullParentKey()
    {
        $parent = m::mock(Model::class);

        // when addConstraints is called we need to return the foreign value
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');
        // when getParentKey is called we want to return null

        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn(null);

        $relation = $this->getRelation($parent);

        $this->related->shouldReceive('getConnectionName')->never();

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('id')->andReturn('foreign.value');
        $model->shouldReceive('getTable')->never();
        $model->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is($model));
    }

    public function testIsNotModelWithNullRelatedKey()
    {
        $relation = $this->getRelation();

        $this->related->shouldReceive('getConnectionName')->never();

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('id')->andReturn(null);
        $model->shouldReceive('getTable')->never();
        $model->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is($model));
    }

    public function testIsNotModelWithAnotherKey()
    {
        $relation = $this->getRelation();

        $this->related->shouldReceive('getConnectionName')->never();

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('id')->andReturn('foreign.value.two');
        $model->shouldReceive('getTable')->never();
        $model->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is($model));
    }

    public function testIsNotModelWithAnotherTable()
    {
        $relation = $this->getRelation();

        $this->related->shouldReceive('getConnectionName')->never();

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('id')->andReturn('foreign.value');
        $model->shouldReceive('getTable')->once()->andReturn('table.two');
        $model->shouldReceive('getConnectionName')->never();

        $this->assertFalse($relation->is($model));
    }

    public function testIsNotModelWithAnotherConnection()
    {
        $relation = $this->getRelation();

        $this->related->shouldReceive('getConnectionName')->once()->andReturn('relation');

        $model = m::mock(Model::class);
        $model->shouldReceive('getAttribute')->once()->with('id')->andReturn('foreign.value');
        $model->shouldReceive('getTable')->once()->andReturn('relation');
        $model->shouldReceive('getConnectionName')->once()->andReturn('relation.two');

        $this->assertFalse($relation->is($model));
    }

    protected function getRelationAssociate($parent)
    {
        $builder = m::mock(Builder::class);
        $builder->shouldReceive('where')->with('relation.id', '=', 'foreign.value');
        $related = m::mock(Model::class);
        $related->shouldReceive('getKey')->andReturn(1);
        $related->shouldReceive('getTable')->andReturn('relation');
        $builder->shouldReceive('getModel')->andReturn($related);

        return new MorphTo($builder, $parent, 'foreign_key', 'id', 'morph_type', 'relation');
    }

    public function getRelation($parent = null, $builder = null)
    {
        $this->builder = $builder ?: m::mock(Builder::class);
        $this->builder->shouldReceive('where')->with('relation.id', '=', 'foreign.value');
        $this->related = m::mock(Model::class);
        $this->related->shouldReceive('getKeyName')->andReturn('id');
        $this->related->shouldReceive('getTable')->andReturn('relation');
        $this->builder->shouldReceive('getModel')->andReturn($this->related);
        $parent = $parent ?: new EloquentMorphToModelStub;

        return m::mock(MorphTo::class.'[createModelByType]', [$this->builder, $parent, 'foreign_key', 'id', 'morph_type', 'relation']);
    }
}

class EloquentMorphToModelStub extends Model
{
    public $foreign_key = 'foreign.value';

    public $table = 'eloquent_morph_to_model_stubs';

    public function relation()
    {
        return $this->morphTo();
    }
}

class EloquentMorphToRelatedStub extends Model
{
    public $table = 'eloquent_morph_to_related_stubs';
}
