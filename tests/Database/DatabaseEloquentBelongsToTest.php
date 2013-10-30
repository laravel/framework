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
		$this->doUpdateMethodRetrievesModelAndUpdate();
	}

	public function testUpdateMethodRetrievesModelAndUpdatesWithOtherKey()
	{
		$this->doUpdateMethodRetrievesModelAndUpdate('other_key');
	}

	protected function doUpdateMethodRetrievesModelAndUpdate($otherKey = null)
	{
		$relation = $this->getRelation(null, $otherKey);
		$mock = m::mock('Illuminate\Database\Eloquent\Model');
		$mock->shouldReceive('fill')->once()->with(array('attributes'))->andReturn($mock);
		$mock->shouldReceive('save')->once()->andReturn(true);
		$relation->getQuery()->shouldReceive('first')->once()->andReturn($mock);

		$this->assertTrue($relation->update(array('attributes')));
	}


	public function testEagerConstraintsAreProperlyAdded()
	{
		$relation = $this->getRelation();
		$relation->getQuery()->shouldReceive('whereIn')->once()->with('relation.id', array('foreign.value', 'foreign.value.two'));
		$models = array(new EloquentBelongsToModelStub, new EloquentBelongsToModelStub, new AnotherEloquentBelongsToModelStub);
		$relation->addEagerConstraints($models);
	}

	public function testEagerConstraintsAreProperlyAddedWithOtherKey()
	{
		$relation = $this->getRelation(null, 'other_key');
		$relation->getQuery()->shouldReceive('whereIn')->once()->with('relation.other_key', array('other.value', 'other.value.two'));
		$models = array(new EloquentBelongsToModelStub, new EloquentBelongsToModelStub, new AnotherEloquentBelongsToModelStub);
		$relation->addEagerConstraints($models);
	}


	public function testRelationIsProperlyInitialized()
	{
		$this->doRelationIsProperlyInitialized();
	}

	public function testRelationIsProperlyInitializedWithOtherKey()
	{
		$this->doRelationIsProperlyInitialized('other_key');
	}

	protected function doRelationIsProperlyInitialized($otherKey = null)
	{
		$relation = $this->getRelation(null, $otherKey);
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('setRelation')->once()->with('foo', null);
		$models = $relation->initRelation(array($model), 'foo');

		$this->assertEquals(array($model), $models);
	}


	public function testModelsAreProperlyMatchedToParents()
	{
		$this->doModelsAreProperlyMatchedToParents();
	}

	public function testModelsAreProperlyMatchedToParentsWithOtherKey()
	{
		$this->doModelsAreProperlyMatchedToParents('other_key');
	}

	protected function doModelsAreProperlyMatchedToParents($otherKey = null)
	{
		$relation = $this->getRelation(null, $otherKey);
		$result1 = m::mock('stdClass');
		$result1->shouldReceive('getKey')->andReturn(1);
		$result2 = m::mock('stdClass');
		$result2->shouldReceive('getKey')->andReturn(2);
		$model1 = new EloquentBelongsToModelStub;
		$model1->foreign_key = 1;
		$model2 = new EloquentBelongsToModelStub;
		$model2->foreign_key = 2;
		$models = $relation->match(array($model1, $model2), new Collection(array($result1, $result2)), 'foo');

		$this->assertEquals(1, $models[0]->foo->getKey());
		$this->assertEquals(2, $models[1]->foo->getKey());
	}


	public function testAssociateMethodSetsForeignKeyOnModel()
	{
		$this->doAssociateMethodSetsForeignKeyOnModel();
	}

	public function testAssociateMethodSetsForeignKeyOnModelWithOtherKey()
	{
		$this->doAssociateMethodSetsForeignKeyOnModel('other_key');
	}

	protected function doAssociateMethodSetsForeignKeyOnModel($otherKey = null)
	{
		$parent = m::mock('Illuminate\Database\Eloquent\Model');
		$parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');
		$relation = $this->getRelation($parent, $otherKey);
		$associate = m::mock('Illuminate\Database\Eloquent\Model');
		$associate->shouldReceive('getKey')->once()->andReturn(1);
		$parent->shouldReceive('setAttribute')->once()->with('foreign_key', 1);
		$parent->shouldReceive('setRelation')->once()->with('relation', $associate);

		$relation->associate($associate);
	}


	protected function getRelation($parent = null, $otherKey = null)
	{
		$relation = $otherKey ?: 'id';

		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$builder->shouldReceive('where')->with('relation.'.$relation, '=', 'foreign.value');
		$related = m::mock('Illuminate\Database\Eloquent\Model');

		if (is_null($otherKey)) {
			$related->shouldReceive('getKeyName')->andReturn('id');
		}

		$related->shouldReceive('getTable')->andReturn('relation');
		$builder->shouldReceive('getModel')->andReturn($related);
		$parent = $parent ?: new EloquentBelongsToModelStub;
		
		return new BelongsTo($builder, $parent, 'foreign_key', 'relation', $otherKey);
	}

}

class EloquentBelongsToModelStub extends Illuminate\Database\Eloquent\Model {

	public $foreign_key = 'foreign.value';
	public $other_key = 'other.value';

}

class AnotherEloquentBelongsToModelStub extends Illuminate\Database\Eloquent\Model {

	public $foreign_key = 'foreign.value.two';
	public $other_key = 'other.value.two';

}