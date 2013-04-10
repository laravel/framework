<?php

use Mockery as m;

class DatabaseEloquentModelTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();

		Illuminate\Database\Eloquent\Model::unsetEventDispatcher();
	}


	public function testAttributeManipulation()
	{
		$model = new EloquentModelStub;
		$model->name = 'foo';
		$this->assertEquals('foo', $model->name);
		$this->assertTrue(isset($model->name));
		unset($model->name);
		$this->assertFalse(isset($model->name));

		// test mutation
		$model->list_items = array('name' => 'taylor');
		$this->assertEquals(array('name' => 'taylor'), $model->list_items);
		$attributes = $model->getAttributes();
		$this->assertEquals(json_encode(array('name' => 'taylor')), $attributes['list_items']);
	}


	public function testCalculatedAttributes()
	{
		$model = new EloquentModelStub;
		$model->password = 'secret';
		$attributes = $model->getAttributes();

		// ensure password attribute was not set to null
		$this->assertFalse(array_key_exists('password', $attributes));
		$this->assertEquals('******', $model->password);
		$this->assertEquals('5ebe2294ecd0e0f08eab7690d2a6ee69', $attributes['password_hash']);
		$this->assertEquals('5ebe2294ecd0e0f08eab7690d2a6ee69', $model->password_hash);
	}


	public function testNewInstanceReturnsNewInstanceWithAttributesSet()
	{
		$model = new EloquentModelStub;
		$instance = $model->newInstance(array('name' => 'taylor'));
		$this->assertInstanceOf('EloquentModelStub', $instance);
		$this->assertEquals('taylor', $instance->name);
	}


	public function testCreateMethodSavesNewModel()
	{
		$_SERVER['__eloquent.saved'] = false;
		$model = EloquentModelSaveStub::create(array('name' => 'taylor'));
		$this->assertTrue($_SERVER['__eloquent.saved']);
		$this->assertEquals('taylor', $model->name);
	}


	public function testFindMethodCallsQueryBuilderCorrectly()
	{
		$result = EloquentModelFindStub::find(1);
		$this->assertEquals('foo', $result);
	}


	public function testFindMethodWithArrayCallsQueryBuilderCorrectly()
	{
		$result = EloquentModelFindManyStub::find(array(1, 2));
		$this->assertEquals('foo', $result);
	}


	public function testDestroyMethodCallsQueryBuilderCorrectly()
	{
		$result = EloquentModelDestroyStub::destroy(1, 2, 3);
	}


	public function testWithMethodCallsQueryBuilderCorrectly()
	{
		$result = EloquentModelWithStub::with('foo', 'bar');
		$this->assertEquals('foo', $result);
	}


	public function testWithMethodCallsQueryBuilderCorrectlyWithArray()
	{
		$result = EloquentModelWithStub::with(array('foo', 'bar'));
		$this->assertEquals('foo', $result);
	}


	public function testUpdateProcess()
	{
		$model = $this->getMock('EloquentModelStub', array('newQuery', 'updateTimestamps'));
		$query = m::mock('Illuminate\Database\Eloquent\Builder');
		$query->shouldReceive('where')->once()->with('id', '=', 1);
		$query->shouldReceive('update')->once()->with(array('id' => 1, 'name' => 'taylor'));
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->once())->method('updateTimestamps');
		$model->setEventDispatcher($events = m::mock('Illuminate\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('until')->once()->with('eloquent.updating: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('fire')->once()->with('eloquent.updated: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('fire')->once()->with('eloquent.saved: '.get_class($model), $model)->andReturn(true);

		$model->foo = 'bar';
		// make sure foo isn't synced so we can test that dirty attributes only are updated
		$model->syncOriginal();
		$model->id = 1;
		$model->name = 'taylor';
		$model->exists = true;
		$this->assertTrue($model->save());
	}


	public function testSaveIsCancelledIfSavingEventReturnsFalse()
	{
		$model = $this->getMock('EloquentModelStub', array('newQuery'));
		$query = m::mock('Illuminate\Database\Eloquent\Builder');
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->setEventDispatcher($events = m::mock('Illuminate\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(false);
		$model->exists = true;

		$this->assertFalse($model->save());
	}
	

	public function testUpdateIsCancelledIfUpdatingEventReturnsFalse()
	{
		$model = $this->getMock('EloquentModelStub', array('newQuery'));
		$query = m::mock('Illuminate\Database\Eloquent\Builder');
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->setEventDispatcher($events = m::mock('Illuminate\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('until')->once()->with('eloquent.updating: '.get_class($model), $model)->andReturn(false);
		$model->exists = true;
		$model->foo = 'bar';

		$this->assertFalse($model->save());
	}


	public function testUpdateProcessWithoutTimestamps()
	{
		$model = $this->getMock('EloquentModelStub', array('newQuery', 'updateTimestamps', 'fireModelEvent'));
		$model->timestamps = false;
		$query = m::mock('Illuminate\Database\Eloquent\Builder');
		$query->shouldReceive('where')->once()->with('id', '=', 1);
		$query->shouldReceive('update')->once()->with(array('id' => 1, 'name' => 'taylor'));
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->never())->method('updateTimestamps');
		$model->expects($this->any())->method('fireModelEvent')->will($this->returnValue(true));

		$model->id = 1;
		$model->name = 'taylor';
		$model->exists = true;
		$this->assertTrue($model->save());
	}


	public function testTimestampsAreReturnedAsObjects()
	{
		$model = $this->getMock('EloquentDateModelStub', array('getDateFormat'));
		$model->expects($this->any())->method('getDateFormat')->will($this->returnValue('Y-m-d'));
		$model->setRawAttributes(array(
			'created_at'	=> '2012-12-04',
			'updated_at'	=> '2012-12-05',
		));

		$this->assertInstanceOf('DateTime', $model->created_at);
		$this->assertInstanceOf('DateTime', $model->updated_at);
	}


	public function testTimestampsAreReturnedAsObjectsOnCreate()
	{
		$timestamps = array(
			'created_at' => new DateTime,
			'updated_at' => new DateTime
		);
		$model = new EloquentDateModelStub;
		Illuminate\Database\Eloquent\Model::setConnectionResolver($resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'));
		$resolver->shouldReceive('connection')->andReturn($mockConnection = m::mock('StdClass'));
		$mockConnection->shouldReceive('getQueryGrammar')->andReturn($mockConnection);
		$mockConnection->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
		$instance = $model->newInstance($timestamps);
		$this->assertInstanceOf('DateTime', $instance->updated_at);
		$this->assertInstanceOf('DateTime', $instance->created_at);
	}


	public function testDateTimeAttributesReturnNullIfSetToNull()
	{
		$timestamps = array(
			'created_at' => new DateTime,
			'updated_at' => new DateTime
		);
		$model = new EloquentDateModelStub;
		Illuminate\Database\Eloquent\Model::setConnectionResolver($resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'));
		$resolver->shouldReceive('connection')->andReturn($mockConnection = m::mock('StdClass'));
		$mockConnection->shouldReceive('getQueryGrammar')->andReturn($mockConnection);
		$mockConnection->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
		$instance = $model->newInstance($timestamps);

		$instance->created_at = null;
		$this->assertNull($instance->created_at);
	}



	public function testInsertProcess()
	{
		$model = $this->getMock('EloquentModelStub', array('newQuery', 'updateTimestamps'));
		$query = m::mock('Illuminate\Database\Eloquent\Builder');
		$query->shouldReceive('insertGetId')->once()->with(array('name' => 'taylor'), 'id')->andReturn(1);
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->once())->method('updateTimestamps');

		$model->setEventDispatcher($events = m::mock('Illuminate\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('until')->once()->with('eloquent.creating: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('fire')->once()->with('eloquent.created: '.get_class($model), $model);
		$events->shouldReceive('fire')->once()->with('eloquent.saved: '.get_class($model), $model);

		$model->name = 'taylor';
		$model->exists = false;
		$this->assertTrue($model->save());
		$this->assertEquals(1, $model->id);
		$this->assertTrue($model->exists);

		$model = $this->getMock('EloquentModelStub', array('newQuery', 'updateTimestamps'));
		$query = m::mock('Illuminate\Database\Eloquent\Builder');
		$query->shouldReceive('insert')->once()->with(array('name' => 'taylor'));
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->once())->method('updateTimestamps');
		$model->setIncrementing(false);

		$model->setEventDispatcher($events = m::mock('Illuminate\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('until')->once()->with('eloquent.creating: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('fire')->once()->with('eloquent.created: '.get_class($model), $model);
		$events->shouldReceive('fire')->once()->with('eloquent.saved: '.get_class($model), $model);

		$model->name = 'taylor';
		$model->exists = false;
		$this->assertTrue($model->save());
		$this->assertNull($model->id);
		$this->assertTrue($model->exists);
	}


	public function testInsertIsCancelledIfCreatingEventReturnsFalse()
	{
		$model = $this->getMock('EloquentModelStub', array('newQuery'));
		$query = m::mock('Illuminate\Database\Eloquent\Builder');
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->setEventDispatcher($events = m::mock('Illuminate\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('until')->once()->with('eloquent.creating: '.get_class($model), $model)->andReturn(false);

		$this->assertFalse($model->save());
		$this->assertFalse($model->exists);
	}


	public function testDeleteProperlyDeletesModel()
	{
		$model = $this->getMock('Illuminate\Database\Eloquent\Model', array('newQuery', 'updateTimestamps', 'touchOwners'));
		$query = m::mock('stdClass');
		$query->shouldReceive('where')->once()->with('id', 1)->andReturn($query);
		$query->shouldReceive('delete')->once();
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->once())->method('touchOwners');
		$model->exists = true;
		$model->id = 1;
		$model->delete();
	}


	public function testNewQueryReturnsEloquentQueryBuilder()
	{
		$conn = m::mock('Illuminate\Database\Connection');
		$grammar = m::mock('Illuminate\Database\Query\Grammars\Grammar');
		$processor = m::mock('Illuminate\Database\Query\Processors\Processor');
		$conn->shouldReceive('getQueryGrammar')->once()->andReturn($grammar);
		$conn->shouldReceive('getPostProcessor')->once()->andReturn($processor);
		EloquentModelStub::setConnectionResolver($resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'));
		$resolver->shouldReceive('connection')->andReturn($conn);
		$model = new EloquentModelStub;
		$builder = $model->newQuery();
		$this->assertInstanceOf('Illuminate\Database\Eloquent\Builder', $builder);
	}


	public function testGetAndSetTableOperations()
	{
		$model = new EloquentModelStub;
		$this->assertEquals('stub', $model->getTable());
		$model->setTable('foo');
		$this->assertEquals('foo', $model->getTable());
	}


	public function testGetKeyReturnsValueOfPrimaryKey()
	{
		$model = new EloquentModelStub;
		$model->id = 1;
		$this->assertEquals(1, $model->getKey());
		$this->assertEquals('id', $model->getKeyName());
	}


	public function testConnectionManagement()
	{
		EloquentModelStub::setConnectionResolver($resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'));
		$model = new EloquentModelStub;
		$model->setConnection('foo');
		$resolver->shouldReceive('connection')->once()->with('foo')->andReturn('bar');

		$this->assertEquals('bar', $model->getConnection());
	}


	public function testToArray()
	{
		$model = new EloquentModelStub;
		$model->name = 'foo';
		$model->age = null;
		$model->password = 'password1';
		$model->setHidden(array('password'));
		$model->setRelation('names', new Illuminate\Database\Eloquent\Collection(array(
			new EloquentModelStub(array('bar' => 'baz')), new EloquentModelStub(array('bam' => 'boom'))
		)));
		$model->setRelation('partner', new EloquentModelStub(array('name' => 'abby')));
		$array = $model->toArray();

		$this->assertTrue(is_array($array));
		$this->assertEquals('foo', $array['name']);
		$this->assertEquals('baz', $array['names'][0]['bar']);
		$this->assertEquals('boom', $array['names'][1]['bam']);
		$this->assertEquals('abby', $array['partner']['name']);
		$this->assertFalse(isset($array['password']));
	}


	public function testHiddenCanAlsoExcludeRelationships()
	{
		$model = new EloquentModelStub;
		$model->name = 'Taylor';
		$model->setRelation('foo', array('bar'));
		$model->setHidden(array('foo'));
		$array = $model->toArray();

		$this->assertEquals(array('name' => 'Taylor'), $array);
	}


	public function testToArraySnakeAttributes()
	{
		$model = new EloquentModelStub;
		$model->setRelation('namesList', new Illuminate\Database\Eloquent\Collection(array(
			new EloquentModelStub(array('bar' => 'baz')), new EloquentModelStub(array('bam' => 'boom'))
		)));
		$array = $model->toArray();

		$this->assertEquals('baz', $array['names_list'][0]['bar']);
		$this->assertEquals('boom', $array['names_list'][1]['bam']);

		$model = new EloquentModelCamelStub;
		$model->setRelation('namesList', new Illuminate\Database\Eloquent\Collection(array(
			new EloquentModelStub(array('bar' => 'baz')), new EloquentModelStub(array('bam' => 'boom'))
		)));
		$array = $model->toArray();

		$this->assertEquals('baz', $array['namesList'][0]['bar']);
		$this->assertEquals('boom', $array['namesList'][1]['bam']);
	}


	public function testToArrayUsesMutators()
	{
		$model = new EloquentModelStub;
		$model->list_items = array(1, 2, 3);
		$array = $model->toArray();

		$this->assertEquals(array(1, 2, 3), $array['list_items']);	
	}


	public function testFillable()
	{
		$model = new EloquentModelStub;
		$model->fillable(array('name', 'age'));
		$model->fill(array('name' => 'foo', 'age' => 'bar'));
		$this->assertEquals('foo', $model->name);
		$this->assertEquals('bar', $model->age);
	}


	public function testUnguardAllowsAnythingToBeSet()
	{
		$model = new EloquentModelStub;
		EloquentModelStub::unguard();
		$model->guard(array('*'));
		$model->fill(array('name' => 'foo', 'age' => 'bar'));
		$this->assertEquals('foo', $model->name);
		$this->assertEquals('bar', $model->age);
		EloquentModelStub::setUnguardState(false);
	}


	public function testUnderscorePropertiesAreNotFilled()
	{
		$model = new EloquentModelStub;
		$model->fill(array('_method' => 'PUT'));
		$this->assertEquals(array(), $model->getAttributes());
	}


	public function testGuarded()
	{
		$model = new EloquentModelStub;
		$model->guard(array('name', 'age'));
		$model->fill(array('name' => 'foo', 'age' => 'bar', 'foo' => 'bar'));
		$this->assertFalse(isset($model->name));
		$this->assertFalse(isset($model->age));
		$this->assertEquals('bar', $model->foo);
	}


	/**
	 * @expectedException Illuminate\Database\Eloquent\MassAssignmentException
	 */
	public function testGlobalGuarded()
	{
		$model = new EloquentModelStub;
		$model->guard(array('*'));
		$model->fill(array('name' => 'foo', 'age' => 'bar', 'votes' => 'baz'));
	}


	public function testHasOneCreatesProperRelation()
	{
		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->hasOne('EloquentModelSaveStub');
		$this->assertEquals('save_stub.eloquent_model_stub_id', $relation->getForeignKey());

		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->hasOne('EloquentModelSaveStub', 'foo');
		$this->assertEquals('save_stub.foo', $relation->getForeignKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof EloquentModelSaveStub);
	}


	public function testMorphOneCreatesProperRelation()
	{
		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->morphOne('EloquentModelSaveStub', 'morph');
		$this->assertEquals('save_stub.morph_id', $relation->getForeignKey());
		$this->assertEquals('save_stub.morph_type', $relation->getMorphType());
		$this->assertEquals('EloquentModelStub', $relation->getMorphClass());
	}


	public function testHasManyCreatesProperRelation()
	{
		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->hasMany('EloquentModelSaveStub');
		$this->assertEquals('save_stub.eloquent_model_stub_id', $relation->getForeignKey());

		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->hasMany('EloquentModelSaveStub', 'foo');
		$this->assertEquals('save_stub.foo', $relation->getForeignKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof EloquentModelSaveStub);
	}


	public function testMorphManyCreatesProperRelation()
	{
		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->morphMany('EloquentModelSaveStub', 'morph');
		$this->assertEquals('save_stub.morph_id', $relation->getForeignKey());
		$this->assertEquals('save_stub.morph_type', $relation->getMorphType());
		$this->assertEquals('EloquentModelStub', $relation->getMorphClass());
	}


	public function testBelongsToCreatesProperRelation()
	{
		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->belongsToStub();
		$this->assertEquals('belongs_to_stub_id', $relation->getForeignKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof EloquentModelSaveStub);

		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->belongsToExplicitKeyStub();
		$this->assertEquals('foo', $relation->getForeignKey());
	}


	public function testMorphToCreatesProperRelation()
	{
		$model = m::mock('Illuminate\Database\Eloquent\Model[belongsTo]');
		$model->foo_type = 'FooClass';
		$model->shouldReceive('belongsTo')->with('FooClass', 'foo_id');
		$relation = $model->morphTo('foo');

		$model = m::mock('EloquentModelStub[belongsTo]');
		$model->morph_to_stub_type = 'FooClass';
		$model->shouldReceive('belongsTo')->with('FooClass', 'morph_to_stub_id');
		$relation = $model->morphToStub();
	}


	public function testBelongsToManyCreatesProperRelation()
	{
		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->belongsToMany('EloquentModelSaveStub');
		$this->assertEquals('eloquent_model_save_stub_eloquent_model_stub.eloquent_model_stub_id', $relation->getForeignKey());
		$this->assertEquals('eloquent_model_save_stub_eloquent_model_stub.eloquent_model_save_stub_id', $relation->getOtherKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof EloquentModelSaveStub);

		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->belongsToMany('EloquentModelSaveStub', 'table', 'foreign', 'other');
		$this->assertEquals('table.foreign', $relation->getForeignKey());
		$this->assertEquals('table.other', $relation->getOtherKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof EloquentModelSaveStub);
	}


	public function testModelsAssumeTheirName()
	{
		$model = new EloquentModelWithoutTableStub;
		$this->assertEquals('eloquent_model_without_table_stubs', $model->getTable());

		require_once __DIR__.'/stubs/EloquentModelNamespacedStub.php';
		$namespacedModel = new Foo\Bar\EloquentModelNamespacedStub;
		$this->assertEquals('foo_bar_eloquent_model_namespaced_stubs', $namespacedModel->getTable());
	}


	public function testTheMutatorCacheIsPopulated()
	{
		$class = new EloquentModelStub;

		$this->assertEquals(array('list_items', 'password'), $class->getMutatedAttributes());
	}


	protected function addMockConnection($model)
	{
		$model->setConnectionResolver($resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'));
		$resolver->shouldReceive('connection')->andReturn(m::mock('Illuminate\Database\Connection'));
		$model->getConnection()->shouldReceive('getQueryGrammar')->andReturn(m::mock('Illuminate\Database\Query\Grammars\Grammar'));
		$model->getConnection()->shouldReceive('getPostProcessor')->andReturn(m::mock('Illuminate\Database\Query\Processors\Processor'));
	}

}

class EloquentModelStub extends Illuminate\Database\Eloquent\Model {
	protected $table = 'stub';
	protected $guarded = array();
	public function getListItemsAttribute($value)
	{
		return json_decode($value, true);
	}
	public function setListItemsAttribute($value)
	{
		$this->attributes['list_items'] = json_encode($value);
	}
	public function getPasswordAttribute()
	{
		return '******';
	}
	public function setPasswordAttribute($value)
	{
		$this->attributes['password_hash'] = md5($value);
	}
	public function belongsToStub()
	{
		return $this->belongsTo('EloquentModelSaveStub');
	}
	public function morphToStub()
	{
		return $this->morphTo();
	}
	public function belongsToExplicitKeyStub()
	{
		return $this->belongsTo('EloquentModelSaveStub', 'foo');
	}
}

class EloquentModelCamelStub extends EloquentModelStub {
	public static $snakeAttributes = false;
}

class EloquentDateModelStub extends EloquentModelStub {
	protected $dates = array('created_at', 'updated_at');
}

class EloquentModelSaveStub extends Illuminate\Database\Eloquent\Model {
	protected $table = 'save_stub';
	protected $guarded = array();
	public function save(array $options = array()) { $_SERVER['__eloquent.saved'] = true; }
	public function setIncrementing($value)
	{
		$this->incrementing = $value;
	}
}

class EloquentModelFindStub extends Illuminate\Database\Eloquent\Model {
	public function newQuery()
	{
		$mock = m::mock('Illuminate\Database\Eloquent\Builder');
		$mock->shouldReceive('find')->once()->with(1, array('*'))->andReturn('foo');
		return $mock;
	}
}

class EloquentModelDestroyStub extends Illuminate\Database\Eloquent\Model {
	public function newQuery()
	{
		$mock = m::mock('Illuminate\Database\Eloquent\Builder');
		$mock->shouldReceive('whereIn')->once()->with('id', array(1, 2, 3))->andReturn($mock);
		$mock->shouldReceive('get')->once()->andReturn(array($model = m::mock('StdClass')));
		$model->shouldReceive('delete')->once();
		return $mock;
	}
}

class EloquentModelFindManyStub extends Illuminate\Database\Eloquent\Model {
	public function newQuery()
	{
		$mock = m::mock('Illuminate\Database\Eloquent\Builder');
		$mock->shouldReceive('whereIn')->once()->with('id', array(1, 2))->andReturn($mock);
		$mock->shouldReceive('get')->once()->with(array('*'))->andReturn('foo');
		return $mock;
	}
}

class EloquentModelWithStub extends Illuminate\Database\Eloquent\Model {
	public function newQuery()
	{
		$mock = m::mock('Illuminate\Database\Eloquent\Builder');
		$mock->shouldReceive('with')->once()->with(array('foo', 'bar'))->andReturn('foo');
		return $mock;
	}
}

class EloquentModelWithoutTableStub extends Illuminate\Database\Eloquent\Model {}
