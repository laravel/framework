<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DatabaseEloquentBelongsToManyTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testModelsAreProperlyHydrated()
	{
		$model1 = new EloquentBelongsToManyModelStub;
		$model1->fill(array('name' => 'taylor', 'pivot_user_id' => 1, 'pivot_role_id' => 2));
		$model2 = new EloquentBelongsToManyModelStub;
		$model2->fill(array('name' => 'dayle', 'pivot_user_id' => 3, 'pivot_role_id' => 4));
		$models = array($model1, $model2);

		$relation = $this->getRelation();
		$relation->getParent()->shouldReceive('getConnectionName')->andReturn('foo.connection');
		$relation->getQuery()->shouldReceive('getModels')->once()->with(array('roles.*', 'user_role.user_id as pivot_user_id', 'user_role.role_id as pivot_role_id'))->andReturn($models);
		$relation->getQuery()->shouldReceive('eagerLoadRelations')->once()->with($models)->andReturn($models);
		$relation->getRelated()->shouldReceive('newCollection')->andReturnUsing(function($array) { return new Collection($array); });
		$results = $relation->get();

		$this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $results);

		// Make sure the foreign keys were set on the pivot models...
		$this->assertEquals('user_id', $results[0]->pivot->getForeignKey());
		$this->assertEquals('role_id', $results[0]->pivot->getOtherKey());
		
		$this->assertEquals('taylor', $results[0]->name);
		$this->assertEquals(1, $results[0]->pivot->user_id);
		$this->assertEquals(2, $results[0]->pivot->role_id);
		$this->assertEquals('foo.connection', $results[0]->pivot->getConnectionName());
		$this->assertEquals('dayle', $results[1]->name);
		$this->assertEquals(3, $results[1]->pivot->user_id);
		$this->assertEquals(4, $results[1]->pivot->role_id);
		$this->assertEquals('foo.connection', $results[1]->pivot->getConnectionName());
		$this->assertEquals('user_role', $results[0]->pivot->getTable());
		$this->assertTrue($results[0]->pivot->exists);
	}


	public function testTimestampsCanBeRetrieveProperly()
	{
		$model1 = new EloquentBelongsToManyModelStub;
		$model1->fill(array('name' => 'taylor', 'pivot_user_id' => 1, 'pivot_role_id' => 2));
		$model2 = new EloquentBelongsToManyModelStub;
		$model2->fill(array('name' => 'dayle', 'pivot_user_id' => 3, 'pivot_role_id' => 4));
		$models = array($model1, $model2);

		$relation = $this->getRelation()->withTimestamps();
		$relation->getParent()->shouldReceive('getConnectionName')->andReturn('foo.connection');
		$relation->getQuery()->shouldReceive('getModels')->once()->with(array(
			'roles.*',
			'user_role.user_id as pivot_user_id',
			'user_role.role_id as pivot_role_id',
			'user_role.created_at as pivot_created_at',
			'user_role.updated_at as pivot_updated_at',
		))->andReturn($models);
		$relation->getQuery()->shouldReceive('eagerLoadRelations')->once()->with($models)->andReturn($models);
		$relation->getRelated()->shouldReceive('newCollection')->andReturnUsing(function($array) { return new Collection($array); });
		$results = $relation->get();
	}


	public function testModelsAreProperlyMatchedToParents()
	{
		$relation = $this->getRelation();

		$result1 = new EloquentBelongsToManyModelPivotStub;
		$result1->pivot->user_id = 1;
		$result2 = new EloquentBelongsToManyModelPivotStub;
		$result2->pivot->user_id = 2;
		$result3 = new EloquentBelongsToManyModelPivotStub;
		$result3->pivot->user_id = 2;

		$model1 = new EloquentBelongsToManyModelStub;
		$model1->id = 1;
		$model2 = new EloquentBelongsToManyModelStub;
		$model2->id = 2;
		$model3 = new EloquentBelongsToManyModelStub;
		$model3->id = 3;

		$relation->getRelated()->shouldReceive('newCollection')->andReturnUsing(function($array) { return new Collection($array); });
		$models = $relation->match(array($model1, $model2, $model3), new Collection(array($result1, $result2, $result3)), 'foo');

		$this->assertEquals(1, $models[0]->foo[0]->pivot->user_id);
		$this->assertEquals(1, count($models[0]->foo));
		$this->assertEquals(2, $models[1]->foo[0]->pivot->user_id);
		$this->assertEquals(2, $models[1]->foo[1]->pivot->user_id);
		$this->assertEquals(2, count($models[1]->foo));
		$this->assertEquals(0, count($models[2]->foo));
	}


	public function testRelationIsProperlyInitialized()
	{
		$relation = $this->getRelation();
		$relation->getRelated()->shouldReceive('newCollection')->andReturnUsing(function($array = array()) { return new Collection($array); });
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('setRelation')->once()->with('foo', m::type('Illuminate\Database\Eloquent\Collection'));
		$models = $relation->initRelation(array($model), 'foo');

		$this->assertEquals(array($model), $models);
	}


	public function testEagerConstraintsAreProperlyAdded()
	{
		$relation = $this->getRelation();
		$relation->getQuery()->shouldReceive('whereIn')->once()->with('user_role.user_id', array(1, 2));
		$model1 = new EloquentBelongsToManyModelStub;
		$model1->id = 1;
		$model2 = new EloquentBelongsToManyModelStub;
		$model2->id = 2;
		$relation->addEagerConstraints(array($model1, $model2));
	}


	public function testAttachInsertsPivotTableRecord()
	{
		$relation = $this->getMock('Illuminate\Database\Eloquent\Relations\BelongsToMany', array('touchIfTouching'), $this->getRelationArguments());
		$query = m::mock('stdClass');
		$query->shouldReceive('from')->once()->with('user_role')->andReturn($query);
		$query->shouldReceive('insert')->once()->with(array(array('user_id' => 1, 'role_id' => 2, 'foo' => 'bar')))->andReturn(true);
		$relation->getQuery()->shouldReceive('getQuery')->andReturn($mockQueryBuilder = m::mock('StdClass'));
		$mockQueryBuilder->shouldReceive('newQuery')->once()->andReturn($query);
		$relation->expects($this->once())->method('touchIfTouching');
		
		$relation->attach(2, array('foo' => 'bar'));
	}


	public function testAttachMultipleInsertsPivotTableRecord()
	{
		$relation = $this->getMock('Illuminate\Database\Eloquent\Relations\BelongsToMany', array('touchIfTouching'), $this->getRelationArguments());
		$query = m::mock('stdClass');
		$query->shouldReceive('from')->once()->with('user_role')->andReturn($query);
		$query->shouldReceive('insert')->once()->with(
			array(
				array('user_id' => 1, 'role_id' => 2, 'foo' => 'bar'),
				array('user_id' => 1, 'role_id' => 3, 'baz' => 'boom', 'foo' => 'bar'),
			)
		)->andReturn(true);
		$relation->getQuery()->shouldReceive('getQuery')->andReturn($mockQueryBuilder = m::mock('StdClass'));
		$mockQueryBuilder->shouldReceive('newQuery')->once()->andReturn($query);
		$relation->expects($this->once())->method('touchIfTouching');

		$relation->attach(array(2, 3 => array('baz' => 'boom')), array('foo' => 'bar'));
	}


	public function testAttachInsertsPivotTableRecordWithTimestampsWhenNecessary()
	{
		$relation = $this->getMock('Illuminate\Database\Eloquent\Relations\BelongsToMany', array('touchIfTouching'), $this->getRelationArguments());
		$relation->withTimestamps();
		$query = m::mock('stdClass');
		$query->shouldReceive('from')->once()->with('user_role')->andReturn($query);
		$query->shouldReceive('insert')->once()->with(array(array('user_id' => 1, 'role_id' => 2, 'foo' => 'bar', 'created_at' => 'time', 'updated_at' => 'time')))->andReturn(true);
		$relation->getQuery()->shouldReceive('getQuery')->andReturn($mockQueryBuilder = m::mock('StdClass'));
		$mockQueryBuilder->shouldReceive('newQuery')->once()->andReturn($query);
		$relation->getParent()->shouldReceive('freshTimestamp')->once()->andReturn('time');
		$relation->expects($this->once())->method('touchIfTouching');
		
		$relation->attach(2, array('foo' => 'bar'));
	}


	public function testDetachRemovesPivotTableRecord()
	{
		$relation = $this->getMock('Illuminate\Database\Eloquent\Relations\BelongsToMany', array('touchIfTouching'), $this->getRelationArguments());
		$query = m::mock('stdClass');
		$query->shouldReceive('from')->once()->with('user_role')->andReturn($query);
		$query->shouldReceive('where')->once()->with('user_id', 1)->andReturn($query);
		$query->shouldReceive('whereIn')->once()->with('role_id', array(1, 2, 3));
		$query->shouldReceive('delete')->once()->andReturn(true);
		$relation->getQuery()->shouldReceive('getQuery')->andReturn($mockQueryBuilder = m::mock('StdClass'));
		$mockQueryBuilder->shouldReceive('newQuery')->once()->andReturn($query);
		$relation->expects($this->once())->method('touchIfTouching');

		$this->assertTrue($relation->detach(array(1, 2, 3)));
	}


	public function testDetachMethodClearsAllPivotRecordsWhenNoIDsAreGiven()
	{
		$relation = $this->getMock('Illuminate\Database\Eloquent\Relations\BelongsToMany', array('touchIfTouching'), $this->getRelationArguments());
		$query = m::mock('stdClass');
		$query->shouldReceive('from')->once()->with('user_role')->andReturn($query);
		$query->shouldReceive('where')->once()->with('user_id', 1)->andReturn($query);
		$query->shouldReceive('whereIn')->never();
		$query->shouldReceive('delete')->once()->andReturn(true);
		$relation->getQuery()->shouldReceive('getQuery')->andReturn($mockQueryBuilder = m::mock('StdClass'));
		$mockQueryBuilder->shouldReceive('newQuery')->once()->andReturn($query);
		$relation->expects($this->once())->method('touchIfTouching');

		$this->assertTrue($relation->detach());
	}


	public function testCreateMethodCreatesNewModelAndInsertsAttachmentRecord()
	{
		$relation = $this->getMock('Illuminate\Database\Eloquent\Relations\BelongsToMany', array('attach'), $this->getRelationArguments());
		$relation->getRelated()->shouldReceive('newInstance')->once()->andReturn($model = m::mock('StdClass'))->with(array('attributes'));
		$model->shouldReceive('save')->once();
		$model->shouldReceive('getKey')->andReturn('foo');
		$relation->expects($this->once())->method('attach')->with('foo', array('joining'));

		$this->assertEquals($model, $relation->create(array('attributes'), array('joining')));
	}


	/**
	 * @dataProvider syncMethodListProvider
	 */
	public function testSyncMethodSyncsIntermediateTableWithGivenArray($list)
	{
		$relation = $this->getMock('Illuminate\Database\Eloquent\Relations\BelongsToMany', array('attach', 'detach'), $this->getRelationArguments());
		$query = m::mock('stdClass');
		$query->shouldReceive('from')->once()->with('user_role')->andReturn($query);
		$query->shouldReceive('where')->once()->with('user_id', 1)->andReturn($query);
		$relation->getQuery()->shouldReceive('getQuery')->andReturn($mockQueryBuilder = m::mock('StdClass'));
		$mockQueryBuilder->shouldReceive('newQuery')->once()->andReturn($query);
		$query->shouldReceive('lists')->once()->with('role_id')->andReturn(array(1, 2, 3));
		$relation->expects($this->once())->method('attach')->with($this->equalTo(4), $this->equalTo(array()), $this->equalTo(false));
		$relation->expects($this->once())->method('detach')->with($this->equalTo(array(1)));
		$relation->getRelated()->shouldReceive('touches')->andReturn(false);
		$relation->getParent()->shouldReceive('touches')->andReturn(false);

		$relation->sync($list);
	}


	public function syncMethodListProvider()
	{
		return array(
			array(array(2, 3, 4)),
			array(array('2', '3', '4')),
		);
	}


	public function testSyncMethodSyncsIntermediateTableWithGivenArrayAndAttributes()
	{
		$relation = $this->getMock('Illuminate\Database\Eloquent\Relations\BelongsToMany', array('attach', 'detach', 'touchIfTouching'), $this->getRelationArguments());
		$query = m::mock('stdClass');
		$query->shouldReceive('from')->once()->with('user_role')->andReturn($query);
		$query->shouldReceive('where')->once()->with('user_id', 1)->andReturn($query);
		$relation->getQuery()->shouldReceive('getQuery')->andReturn($mockQueryBuilder = m::mock('StdClass'));
		$mockQueryBuilder->shouldReceive('newQuery')->once()->andReturn($query);
		$query->shouldReceive('lists')->once()->with('role_id')->andReturn(array(1, 2, 3));
		$relation->expects($this->once())->method('attach')->with($this->equalTo(4), $this->equalTo(array('foo' => 'bar')), $this->equalTo(false));
		$relation->expects($this->once())->method('detach')->with($this->equalTo(array(1)));
		$relation->expects($this->once())->method('touchIfTouching');

		$relation->sync(array(2, 3, 4 => array('foo' => 'bar')));
	}


	public function testTouchMethodSyncsTimestamps()
	{
		$relation = $this->getRelation();
		$relation->getRelated()->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
		$relation->getRelated()->shouldReceive('getQualifiedKeyName')->andReturn('table.id');
		$relation->getQuery()->shouldReceive('select')->once()->with('table.id')->andReturn($relation->getQuery());
		$relation->getQuery()->shouldReceive('lists')->once()->with('id')->andReturn(array(1, 2, 3));
		$relation->getRelated()->shouldReceive('newQuery')->once()->andReturn($query = m::mock('StdClass'));
		$query->shouldReceive('whereIn')->once()->with('id', array(1, 2, 3))->andReturn($query);
		$query->shouldReceive('update')->once()->with(array('updated_at' => new DateTime));

		$relation->touch();
	}


	public function testTouchIfTouching()
	{
		$relation = $this->getMock('Illuminate\Database\Eloquent\Relations\BelongsToMany', array('touch', 'touchingParent'), $this->getRelationArguments());
		$relation->expects($this->once())->method('touchingParent')->will($this->returnValue(true));
		$relation->getParent()->shouldReceive('touch')->once();
		$relation->getParent()->shouldReceive('touches')->once()->with('relation_name')->andReturn(true);
		$relation->expects($this->once())->method('touch');

		$relation->touchIfTouching();
	}


	public function getRelation()
	{
		list($builder, $parent) = $this->getRelationArguments();

		return new BelongsToMany($builder, $parent, 'user_role', 'user_id', 'role_id', 'relation_name');
	}


	public function getRelationArguments()
	{
		$parent = m::mock('Illuminate\Database\Eloquent\Model');
		$parent->shouldReceive('getKey')->andReturn(1);
		$parent->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
		$parent->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');

		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$related = m::mock('Illuminate\Database\Eloquent\Model');
		$builder->shouldReceive('getModel')->andReturn($related);

		$related->shouldReceive('getTable')->andReturn('roles');
		$related->shouldReceive('getKeyName')->andReturn('id');

		$builder->shouldReceive('join')->once()->with('user_role', 'roles.id', '=', 'user_role.role_id');
		$builder->shouldReceive('where')->once()->with('user_role.user_id', '=', 1);

		return array($builder, $parent, 'user_role', 'user_id', 'role_id', 'relation_name');
	}

}

class EloquentBelongsToManyModelStub extends Illuminate\Database\Eloquent\Model {
	protected $guarded = array();
}

class EloquentBelongsToManyModelPivotStub extends Illuminate\Database\Eloquent\Model {
	public $pivot;
	public function __construct()
	{
		$this->pivot = new EloquentBelongsToManyPivotStub;
	}
}

class EloquentBelongsToManyPivotStub {
	public $user_id;
}