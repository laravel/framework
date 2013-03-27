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
		$relation = $this->getRelation();
		$mockModel = $this->getMock('Illuminate\Database\Eloquent\Model', array('save'));
		$mockModel->expects($this->once())->method('save');
		$result = $relation->save($mockModel);

		$attributes = $result->getAttributes();
		$this->assertEquals(1, $attributes['foreign_key']);
	}


	public function testCreateMethodProperlyCreatesNewModel()
	{
		$relation = $this->getRelation();
		$created = $this->getMock('Illuminate\Database\Eloquent\Model', array('save', 'getKey', 'setRawAttributes'));
		$created->expects($this->once())->method('save')->will($this->returnValue(true));
		$relation->getRelated()->shouldReceive('newInstance')->once()->andReturn($created);
		$created->expects($this->once())->method('setRawAttributes')->with($this->equalTo(array('name' => 'taylor', 'foreign_key' => 1)));

		$this->assertEquals($created, $relation->create(array('name' => 'taylor')));
	}


	public function testUpdateMethodUpdatesModelsWithTimestamps()
	{
		$relation = $this->getRelation();
		$relation->getRelated()->shouldReceive('usesTimestamps')->once()->andReturn(true);
		$relation->getRelated()->shouldReceive('freshTimestamp')->once()->andReturn(100);
		$relation->getQuery()->shouldReceive('update')->once()->with(array('foo' => 'bar', 'updated_at' => 100))->andReturn('results');

		$this->assertEquals('results', $relation->update(array('foo' => 'bar')));
	}


	public function testRelationIsProperlyInitialized()
	{
		$relation = $this->getRelation();
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('setRelation')->once()->with('foo', null);
		$models = $relation->initRelation(array($model), 'foo');

		$this->assertEquals(array($model), $models);
	}


	public function testEagerConstraintsAreProperlyAdded()
	{
		$relation = $this->getRelation();
		$relation->getQuery()->shouldReceive('whereIn')->once()->with('table.foreign_key', array(1, 2));
		$model1 = new EloquentHasOneModelStub;
		$model1->id = 1;
		$model2 = new EloquentHasOneModelStub;
		$model2->id = 2;
		$relation->addEagerConstraints(array($model1, $model2));
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

		$models = $relation->match(array($model1, $model2, $model3), new Collection(array($result1, $result2)), 'foo');

		$this->assertEquals(1, $models[0]->foo->foreign_key);
		$this->assertEquals(2, $models[1]->foo->foreign_key);
		$this->assertNull($models[2]->foo);
	}


	protected function getRelation()
	{
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$builder->shouldReceive('where')->with('table.foreign_key', '=', 1);
		$related = m::mock('Illuminate\Database\Eloquent\Model');
		$builder->shouldReceive('getModel')->andReturn($related);
		$parent = m::mock('Illuminate\Database\Eloquent\Model');
		$parent->shouldReceive('getKey')->andReturn(1);
		$parent->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
		$parent->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
		return new HasOne($builder, $parent, 'table.foreign_key');
	}

}

class EloquentHasOneModelStub extends Illuminate\Database\Eloquent\Model {
	public $foreign_key = 'foreign.value';
}