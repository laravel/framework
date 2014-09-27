<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseEloquentBelongsToTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testUpdateMethodRetrievesModelAndUpdates()
	{
		$relation = $this->getRelation();
		$mock = m::mock('Illuminate\Database\Eloquent\Model');
		$mock->shouldReceive('fill')->once()->with(['attributes'])->andReturn($mock);
		$mock->shouldReceive('save')->once()->andReturn(true);
		$relation->getQuery()->shouldReceive('first')->once()->andReturn($mock);

		$this->assertTrue($relation->update(['attributes']));
	}


	public function testEagerConstraintsAreProperlyAdded()
	{
		$relation = $this->getRelation();
		$relation->getQuery()->shouldReceive('whereIn')->once()->with('relation.id', ['foreign.value', 'foreign.value.two']);
		$models = [new EloquentBelongsToModelStub, new EloquentBelongsToModelStub, new AnotherEloquentBelongsToModelStub];
		$relation->addEagerConstraints($models);
	}


	public function testRelationIsProperlyInitialized()
	{
		$relation = $this->getRelation();
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('setRelation')->once()->with('foo', null);
		$models = $relation->initRelation([$model], 'foo');

		$this->assertEquals([$model], $models);
	}


	public function testModelsAreProperlyMatchedToParents()
	{
		$relation = $this->getRelation();
		$result1 = m::mock('stdClass');
		$result1->shouldReceive('getAttribute')->with('id')->andReturn(1);
		$result2 = m::mock('stdClass');
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
		$parent = m::mock('Illuminate\Database\Eloquent\Model');
		$parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');
		$relation = $this->getRelation($parent);
		$associate = m::mock('Illuminate\Database\Eloquent\Model');
		$associate->shouldReceive('getAttribute')->once()->with('id')->andReturn(1);
		$parent->shouldReceive('setAttribute')->once()->with('foreign_key', 1);
		$parent->shouldReceive('setRelation')->once()->with('relation', $associate);

		$relation->associate($associate);
	}


	protected function getRelation($parent = null)
	{
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$builder->shouldReceive('where')->with('relation.id', '=', 'foreign.value');
		$related = m::mock('Illuminate\Database\Eloquent\Model');
		$related->shouldReceive('getKeyName')->andReturn('id');
		$related->shouldReceive('getTable')->andReturn('relation');
		$builder->shouldReceive('getModel')->andReturn($related);
		$parent = $parent ?: new EloquentBelongsToModelStub;
		return new BelongsTo($builder, $parent, 'foreign_key', 'id', 'relation');
	}

}

class EloquentBelongsToModelStub extends Illuminate\Database\Eloquent\Model {

	public $foreign_key = 'foreign.value';

}

class AnotherEloquentBelongsToModelStub extends Illuminate\Database\Eloquent\Model {

	public $foreign_key = 'foreign.value.two';

}
