<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DatabaseEloquentMorphToTest extends TestCase
{
    protected $builder;

    protected $related;

    public function tearDown()
    {
        m::close();
    }

    public function testLookupDictionaryIsProperlyConstructed()
    {
        $relation = $this->getRelation();
        $relation->addEagerConstraints([
            $one = (object) ['morph_type' => 'morph_type_1', 'foreign_key' => 'foreign_key_1'],
            $two = (object) ['morph_type' => 'morph_type_1', 'foreign_key' => 'foreign_key_1'],
            $three = (object) ['morph_type' => 'morph_type_2', 'foreign_key' => 'foreign_key_2'],
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
                ],
            ],
        ], $dictionary);
    }

    public function testMorphToWithDefault()
    {
        $relation = $this->getRelation()->withDefault();

        $this->builder->shouldReceive('first')->once()->andReturnNull();

        $newModel = new EloquentMorphToModelStub();

        $this->related->shouldReceive('newInstance')->once()->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());
    }

    public function testMorphToWithDynamicDefault()
    {
        $relation = $this->getRelation()->withDefault(function ($newModel) {
            $newModel->username = 'taylor';
        });

        $this->builder->shouldReceive('first')->once()->andReturnNull();

        $newModel = new EloquentMorphToModelStub();

        $this->related->shouldReceive('newInstance')->once()->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());

        $this->assertSame('taylor', $newModel->username);
    }

    public function testMorphToWithArrayDefault()
    {
        $relation = $this->getRelation()->withDefault(['username' => 'taylor']);

        $this->builder->shouldReceive('first')->once()->andReturnNull();

        $newModel = new EloquentMorphToModelStub();

        $this->related->shouldReceive('newInstance')->once()->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());

        $this->assertSame('taylor', $newModel->username);
    }

    public function testAssociateMethodSetsForeignKeyAndTypeOnModel()
    {
        $parent = m::mock('Illuminate\Database\Eloquent\Model');
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');

        $relation = $this->getRelationAssociate($parent);

        $associate = m::mock('Illuminate\Database\Eloquent\Model');
        $associate->shouldReceive('getKey')->once()->andReturn(1);
        $associate->shouldReceive('getMorphClass')->once()->andReturn('Model');

        $parent->shouldReceive('setAttribute')->once()->with('foreign_key', 1);
        $parent->shouldReceive('setAttribute')->once()->with('morph_type', 'Model');
        $parent->shouldReceive('setRelation')->once()->with('relation', $associate);

        $relation->associate($associate);
    }

    public function testAssociateMethodIgnoresNullValue()
    {
        $parent = m::mock('Illuminate\Database\Eloquent\Model');
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');

        $relation = $this->getRelationAssociate($parent);

        $parent->shouldReceive('setAttribute')->once()->with('foreign_key', null);
        $parent->shouldReceive('setAttribute')->once()->with('morph_type', null);
        $parent->shouldReceive('setRelation')->once()->with('relation', null);

        $relation->associate(null);
    }

    public function testDissociateMethodDeletesUnsetsKeyAndTypeOnModel()
    {
        $parent = m::mock('Illuminate\Database\Eloquent\Model');
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');

        $relation = $this->getRelation($parent);

        $parent->shouldReceive('setAttribute')->once()->with('foreign_key', null);
        $parent->shouldReceive('setAttribute')->once()->with('morph_type', null);
        $parent->shouldReceive('setRelation')->once()->with('relation', null);

        $relation->dissociate();
    }

    protected function getRelationAssociate($parent)
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('where')->with('relation.id', '=', 'foreign.value');
        $related = m::mock('Illuminate\Database\Eloquent\Model');
        $related->shouldReceive('getKey')->andReturn(1);
        $related->shouldReceive('getTable')->andReturn('relation');
        $builder->shouldReceive('getModel')->andReturn($related);

        return new MorphTo($builder, $parent, 'foreign_key', 'id', 'morph_type', 'relation');
    }

    public function getRelation($parent = null, $builder = null)
    {
        $this->builder = $builder ?: m::mock('Illuminate\Database\Eloquent\Builder');
        $this->builder->shouldReceive('where')->with('relation.id', '=', 'foreign.value');
        $this->related = m::mock('Illuminate\Database\Eloquent\Model');
        $this->related->shouldReceive('getKeyName')->andReturn('id');
        $this->related->shouldReceive('getTable')->andReturn('relation');
        $this->builder->shouldReceive('getModel')->andReturn($this->related);
        $parent = $parent ?: new EloquentMorphToModelStub;
        $morphTo = m::mock('Illuminate\Database\Eloquent\Relations\MorphTo[createModelByType]', [$this->builder, $parent, 'foreign_key', 'id', 'morph_type', 'relation']);

        return $morphTo;
    }
}

class EloquentMorphToModelStub extends \Illuminate\Database\Eloquent\Model
{
    public $foreign_key = 'foreign.value';
}
