<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DatabaseEloquentHasOneTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testSaveMethodSetsForeignKeyOnModel()
	{
		$this->doSaveMethodSetsForeignKeyOnModel();
	}

	public function testSaveMethodSetsForeignKeyOnModelWithOtherKey()
	{
		$this->doSaveMethodSetsForeignKeyOnModel('other_key');
	}

	protected function doSaveMethodSetsForeignKeyOnModel($otherKey = null)
	{
		$relation = $this->getRelation($otherKey);
		$mockModel = $this->getMock('Illuminate\Database\Eloquent\Model', array('save'));
		$mockModel->expects($this->once())->method('save')->will($this->returnValue(true));
		$result = $relation->save($mockModel);

		$attributes = $result->getAttributes();
		$this->assertEquals(1, $attributes['foreign_key']);
	}


	public function testCreateMethodProperlyCreatesNewModel()
	{
		$this->doCreateMethodProperlyCreatesNewModel();
	}

	public function testCreateMethodProperlyCreatesNewModelWithOtherKey()
	{
		$this->doCreateMethodProperlyCreatesNewModel('other_key');
	}

	protected function doCreateMethodProperlyCreatesNewModel($otherKey = null)
	{
		$relation = $this->getRelation($otherKey);
		$created = $this->getMock('Illuminate\Database\Eloquent\Model', array('save', 'getKey', 'setRawAttributes'));
		$created->expects($this->once())->method('save')->will($this->returnValue(true));
		$relation->getRelated()->shouldReceive('newInstance')->once()->andReturn($created);
		$created->expects($this->once())->method('setRawAttributes')->with($this->equalTo(array('name' => 'taylor', 'foreign_key' => 1)));

		$this->assertEquals($created, $relation->create(array('name' => 'taylor')));
	}


	public function testUpdateMethodUpdatesModelsWithTimestamps()
	{
		$this->doUpdateMethodUpdatesModelsWithTimestamps();
	}

	public function testUpdateMethodUpdatesModelsWithTimestampsWithOtherKey()
	{
		$this->doUpdateMethodUpdatesModelsWithTimestamps('other_key');
	}

	protected function doUpdateMethodUpdatesModelsWithTimestamps($otherKey = null)
	{
		$relation = $this->getRelation($otherKey);
		$relation->getRelated()->shouldReceive('usesTimestamps')->once()->andReturn(true);
		$relation->getRelated()->shouldReceive('freshTimestamp')->once()->andReturn(100);
		$relation->getRelated()->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
		$relation->getQuery()->shouldReceive('update')->once()->with(array('foo' => 'bar', 'updated_at' => 100))->andReturn('results');

		$this->assertEquals('results', $relation->update(array('foo' => 'bar')));
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
		$relation = $this->getRelation($otherKey);
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('setRelation')->once()->with('foo', null);
		$models = $relation->initRelation(array($model), 'foo');

		$this->assertEquals(array($model), $models);
	}


	public function testEagerConstraintsAreProperlyAdded()
	{
		$this->doEagerConstraintsAreProperlyAdded();
	}

	public function testEagerConstraintsAreProperlyAddedWithOtherKey()
	{
		$this->doEagerConstraintsAreProperlyAdded('other_key');
	}

	protected function doEagerConstraintsAreProperlyAdded($otherKey = null)
	{
		$relation = $this->getRelation($otherKey);
		$relation->getQuery()->shouldReceive('whereIn')->once()->with('table.foreign_key', array(1, 2));
		$model1 = new EloquentHasOneModelStub;
		$model1->id = 1;
		$model2 = new EloquentHasOneModelStub;
		$model2->id = 2;
		$relation->addEagerConstraints(array($model1, $model2));
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
		$relation = $this->getRelation($otherKey);

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

		$models = $relation->match(array($model1, $model2, $model3), new Collection(array($result1, $result2)), 'foo');

		$this->assertEquals(1, $models[0]->foo->foreign_key);
		$this->assertEquals(2, $models[1]->foo->foreign_key);
		$this->assertNull($models[2]->foo);
	}


	public function testRelationCountQueryCanBeBuilt()
	{
		$this->doRelationCountQueryCanBeBuilt();
	}

	public function testRelationCountQueryCanBeBuiltWithOtherKey()
	{
		$this->doRelationCountQueryCanBeBuilt('other_key');
	}

	protected function doRelationCountQueryCanBeBuilt($otherKey = null)
	{
		$relation = $this->getRelation($otherKey);
		$query = m::mock('Illuminate\Database\Eloquent\Builder');
		$query->shouldReceive('select')->once()->with(m::type('Illuminate\Database\Query\Expression'));
		$relation->getParent()->shouldReceive('getQualifiedKeyName')->andReturn('foo');
		$query->shouldReceive('where')->once()->with('table.foreign_key', '=', m::type('Illuminate\Database\Query\Expression'));
		$relation->getParent()->shouldReceive('getQuery')->andReturn($parentQuery = m::mock('StdClass'));
		$parentQuery->shouldReceive('getGrammar')->once()->andReturn($grammar = m::mock('StdClass'));
		$grammar->shouldReceive('wrap')->once()->with('foo');

		$relation->getRelationCountQuery($query);
	}


	protected function getRelation($otherKey = null)
	{
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$builder->shouldReceive('where')->with('table.foreign_key', '=', 1);
		$related = m::mock('Illuminate\Database\Eloquent\Model');
		$builder->shouldReceive('getModel')->andReturn($related);
		$parent = m::mock('Illuminate\Database\Eloquent\Model');

		if (is_null($otherKey)) {
			$parent->shouldReceive('getKey')->andReturn(1);
		} else {
			$parent->shouldReceive('getAttribute')->andReturn(1);
		}

		$parent->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
		$parent->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');

		return new HasOne($builder, $parent, 'table.foreign_key', $otherKey);
	}

}

class EloquentHasOneModelStub extends Illuminate\Database\Eloquent\Model {
	public $foreign_key = 'foreign.value';
}