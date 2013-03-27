<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DatabaseEloquentMorphTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testMorphOneSetsProperConstraints()
	{
		$relation = $this->getOneRelation();
	}


	public function testMorphOneEagerConstraintsAreProperlyAdded()
	{
		$relation = $this->getOneRelation();
		$relation->getQuery()->shouldReceive('whereIn')->once()->with('table.morph_id', array(1, 2));
		$relation->getQuery()->shouldReceive('where')->once()->with('table.morph_type', get_class($relation->getParent()));

		$model1 = new EloquentMorphResetModelStub;
		$model1->id = 1;
		$model2 = new EloquentMorphResetModelStub;
		$model2->id = 2;
		$relation->addEagerConstraints(array($model1, $model2));
	}


	public function testMorphOneWhereClausesCanBeRemoved()
	{
		$builder = new EloquentMorphResetBuilderStub;
		$parent = m::mock('Illuminate\Database\Eloquent\Model');
		$parent->shouldReceive('getKey')->andReturn(1);
		$relation = new MorphOne($builder, $parent, 'morph_type', 'morph_id');
		$relation->where('foo', '=', 'bar');
		list($wheres, $bindings) = $relation->getAndResetWheres();

		$this->assertEquals('bar', $bindings[0]);
		$this->assertEquals('Basic', $wheres[0]['type']);
		$this->assertEquals('foo', $wheres[0]['column']);
		$this->assertEquals('bar', $wheres[0]['value']);
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
		$relation->getQuery()->shouldReceive('whereIn')->once()->with('table.morph_id', array(1, 2));
		$relation->getQuery()->shouldReceive('where')->once()->with('table.morph_type', get_class($relation->getParent()));

		$model1 = new EloquentMorphResetModelStub;
		$model1->id = 1;
		$model2 = new EloquentMorphResetModelStub;
		$model2->id = 2;
		$relation->addEagerConstraints(array($model1, $model2));
	}


	public function testMorphManyWhereClausesCanBeRemoved()
	{
		$builder = new EloquentMorphResetBuilderStub;
		$parent = m::mock('Illuminate\Database\Eloquent\Model');
		$parent->shouldReceive('getKey')->andReturn(1);
		$relation = new MorphMany($builder, $parent, 'morph_type', 'morph_id');
		$relation->where('foo', '=', 'bar');
		list($wheres, $bindings) = $relation->getAndResetWheres();

		$this->assertEquals('bar', $bindings[0]);
		$this->assertEquals('Basic', $wheres[0]['type']);
		$this->assertEquals('foo', $wheres[0]['column']);
		$this->assertEquals('bar', $wheres[0]['value']);
	}


	public function testCreateFunctionOnMorph()
	{
		// Doesn't matter which relation type we use since they share the code...
		$relation = $this->getOneRelation();
		$created = m::mock('stdClass');
		$relation->getRelated()->shouldReceive('newInstance')->once()->with(array('name' => 'taylor', 'table.morph_id' => 1, 'table.morph_type' => get_class($relation->getParent())))->andReturn($created);
		$created->shouldReceive('save')->once()->andReturn(true);

		$this->assertEquals($created, $relation->create(array('name' => 'taylor')));
	}


	protected function getOneRelation()
	{
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$builder->shouldReceive('where')->once()->with('table.morph_id', '=', 1);
		$related = m::mock('Illuminate\Database\Eloquent\Model');
		$builder->shouldReceive('getModel')->andReturn($related);
		$parent = m::mock('Illuminate\Database\Eloquent\Model');
		$parent->shouldReceive('getKey')->andReturn(1);
		$builder->shouldReceive('where')->once()->with('table.morph_type', get_class($parent));
		return new MorphOne($builder, $parent, 'table.morph_type', 'table.morph_id');
	}


	protected function getManyRelation()
	{
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$builder->shouldReceive('where')->once()->with('table.morph_id', '=', 1);
		$related = m::mock('Illuminate\Database\Eloquent\Model');
		$builder->shouldReceive('getModel')->andReturn($related);
		$parent = m::mock('Illuminate\Database\Eloquent\Model');
		$parent->shouldReceive('getKey')->andReturn(1);
		$builder->shouldReceive('where')->once()->with('table.morph_type', get_class($parent));
		return new MorphMany($builder, $parent, 'table.morph_type', 'table.morph_id');
	}

}


class EloquentMorphResetModelStub extends Illuminate\Database\Eloquent\Model {}


class EloquentMorphResetBuilderStub extends Illuminate\Database\Eloquent\Builder {
	public function __construct() { $this->query = new EloquentRelationQueryStub; }
	public function getModel() {}
}


class EloquentMorphQueryStub extends Illuminate\Database\Query\Builder {
	public function __construct() {}
}