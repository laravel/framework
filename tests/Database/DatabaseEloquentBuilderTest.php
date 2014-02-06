<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Builder;

class DatabaseEloquentBuilderTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFindMethod()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('first'), array($this->getMockQuery()));
		$model = $this->getMockModel();
		$model->shouldReceive('getKeyName')->once()->andReturn('foo');
		$builder->setModel($model);
		$builder->getQuery()->shouldReceive('where')->once()->with('foo', '=', 'bar');
		$builder->expects($this->once())->method('first')->with($this->equalTo(array('column')))->will($this->returnValue('baz'));
		$result = $builder->find('bar', array('column'));
		$this->assertEquals('baz', $result);
	}

	/**
	 * @expectedException Illuminate\Database\Eloquent\ModelNotFoundException
	 */
	public function testFindOrFailMethodThrowsModelNotFoundException()
	{
		$query = m::mock('Illuminate\Database\Query\Builder');
		$query->shouldReceive('where')->once()->with('foo', '=', 'bar');
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('first'), array($query));
 		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('getKeyName')->once()->andReturn('foo');
		$model->shouldReceive('getTable')->once()->andReturn('table');
		$query->shouldReceive('from')->once()->with('table');
		$builder->setModel($model);
		$builder->expects($this->once())->method('first')->with($this->equalTo(array('column')))->will($this->returnValue(null));
		$result = $builder->findOrFail('bar', array('column'));
	}

	/**
	 * @expectedException Illuminate\Database\Eloquent\ModelNotFoundException
	 */
	public function testFirstOrFailMethodThrowsModelNotFoundException()
	{
		$query = m::mock('Illuminate\Database\Query\Builder');
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('first'), array($query));
 		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('getTable')->once()->andReturn('table');
		$query->shouldReceive('from')->once()->with('table');
		$builder->setModel($model);
		$builder->expects($this->once())->method('first')->with($this->equalTo(array('column')))->will($this->returnValue(null));
		$result = $builder->firstOrFail(array('column'));
	}

	public function testFindWithMany()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('get'), array($this->getMockQuery()));
		$model = $this->getMockModel();
		$model->shouldReceive('getKeyName')->once()->andReturn('foo');
		$builder->setModel($model);
		$builder->getQuery()->shouldReceive('whereIn')->once()->with('foo', array(1, 2));
		$builder->expects($this->once())->method('get')->with($this->equalTo(array('column')))->will($this->returnValue('baz'));
		$result = $builder->find(array(1, 2), array('column'));
		$this->assertEquals('baz', $result);
	}

	/**
	 * @expectedException Illuminate\Database\Eloquent\ModelNotFoundException
	 */
	public function testFindOrFailShouldThrowAnException()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('find'), array($this->getMockQuery()));
		$builder->expects($this->once())->method('find')->will($this->returnValue(null));
		$builder->findOrFail('bar');
	}


	public function testFirstMethod()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('get', 'take'), array($this->getMockQuery()));
		$collection = m::mock('stdClass');
		$collection->shouldReceive('first')->once()->andReturn('bar');
		$builder->expects($this->once())->method('take')->with($this->equalTo(1))->will($this->returnValue($builder));
		$builder->expects($this->once())->method('get')->with($this->equalTo(array('*')))->will($this->returnValue($collection));

		$result = $builder->first();
		$this->assertEquals('bar', $result);
	}

	/**
	 * @expectedException Illuminate\Database\Eloquent\ModelNotFoundException
	 */
	public function testFirstOrFailShouldThrowAnException()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('first'), array($this->getMockQuery()));
		$builder->expects($this->once())->method('first')->will($this->returnValue(null));
		$builder->firstOrFail('bar');
	}


	public function testGetMethodLoadsModelsAndHydratesEagerRelations()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('getModels', 'eagerLoadRelations'), array($this->getMockQuery()));
		$builder->expects($this->once())->method('getModels')->with($this->equalTo(array('foo')))->will($this->returnValue(array('bar')));
		$builder->expects($this->once())->method('eagerLoadRelations')->with($this->equalTo(array('bar')))->will($this->returnValue(array('bar', 'baz')));
		$model = $this->getMockModel();
		$model->shouldReceive('newCollection')->with(array('bar', 'baz'))->andReturn(new Illuminate\Database\Eloquent\Collection(array('bar', 'baz')));
		$builder->setModel($model);
		$results = $builder->get(array('foo'));

		$this->assertEquals(array('bar', 'baz'), $results->all());
	}


	public function testGetMethodDoesntHydrateEagerRelationsWhenNoResultsAreReturned()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('getModels', 'eagerLoadRelations'), array($this->getMockQuery()));
		$builder->expects($this->once())->method('getModels')->with($this->equalTo(array('foo')))->will($this->returnValue(array()));
		$builder->expects($this->never())->method('eagerLoadRelations');
		$model = $this->getMockModel();
		$model->shouldReceive('newCollection')->with(array())->andReturn(new Illuminate\Database\Eloquent\Collection(array()));
		$builder->setModel($model);
		$results = $builder->get(array('foo'));

		$this->assertEquals(array(), $results->all());
	}


	public function testPluckMethodWithModelFound()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('first'), array($this->getMockQuery()));
		$mockModel = new StdClass;
		$mockModel->name = 'foo';
		$builder->expects($this->any())->method('first')->with($this->equalTo(array('name')))->will($this->returnValue($mockModel));

		$this->assertEquals('foo', $builder->pluck('name'));
	}

	public function testPluckMethodWithModelNotFound()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('first'), array($this->getMockQuery()));
		$builder->expects($this->any())->method('first')->with($this->equalTo(array('name')))->will($this->returnValue(null));

		$this->assertNull($builder->pluck('name'));
	}


	public function testChunkExecuteCallbackOverPaginatedRequest()
	{
		$builder = m::mock('Illuminate\Database\Eloquent\Builder[forPage,get]', array($this->getMockQuery()));
		$builder->shouldReceive('forPage')->once()->with(1, 2)->andReturn($builder);
		$builder->shouldReceive('forPage')->once()->with(2, 2)->andReturn($builder);
		$builder->shouldReceive('forPage')->once()->with(3, 2)->andReturn($builder);
		$builder->shouldReceive('get')->times(3)->andReturn(array('foo1', 'foo2'), array('foo3'), array());

		$callbackExecutionAssertor = m::mock('StdClass');
		$callbackExecutionAssertor->shouldReceive('doSomething')->with('foo1')->once();
		$callbackExecutionAssertor->shouldReceive('doSomething')->with('foo2')->once();
		$callbackExecutionAssertor->shouldReceive('doSomething')->with('foo3')->once();

		$builder->chunk(2, function($results) use($callbackExecutionAssertor) {
			foreach ($results as $result) {
				$callbackExecutionAssertor->doSomething($result);
			}
		});
	}


	public function testListsReturnsTheMutatedAttributesOfAModel()
	{
		$builder = $this->getBuilder();
		$builder->getQuery()->shouldReceive('lists')->with('name', '')->andReturn(array('bar', 'baz'));
		$model = $this->getMockModel();
		$model->shouldReceive('hasGetMutator')->with('name')->andReturn(true);
		$model->shouldReceive('newFromBuilder')->with(array('name' => 'bar'))->andReturn(new EloquentBuilderTestListsStub(array('name' => 'bar')));
		$model->shouldReceive('newFromBuilder')->with(array('name' => 'baz'))->andReturn(new EloquentBuilderTestListsStub(array('name' => 'baz')));
		$builder->setModel($model);

		$this->assertEquals(array('foo_bar', 'foo_baz'), $builder->lists('name'));
	}

	public function testListsWithoutModelGetterJustReturnTheAttributesFoundInDatabase()
	{
		$builder = $this->getBuilder();
		$builder->getQuery()->shouldReceive('lists')->with('name', '')->andReturn(array('bar', 'baz'));
		$model = $this->getMockModel();
		$model->shouldReceive('hasGetMutator')->with('name')->andReturn(false);
		$builder->setModel($model);

		$this->assertEquals(array('bar', 'baz'), $builder->lists('name'));
	}


	public function testWithDeletedProperlyRemovesDeletedClause()
	{
		$builder = new Illuminate\Database\Eloquent\Builder(new Illuminate\Database\Query\Builder(
			m::mock('Illuminate\Database\ConnectionInterface'),
			m::mock('Illuminate\Database\Query\Grammars\Grammar'),
			m::mock('Illuminate\Database\Query\Processors\Processor')
		));
		$model = $this->getMockModel();
		$model->shouldReceive('getQualifiedDeletedAtColumn')->once()->andReturn('deleted_at');
		$builder->setModel($model);

		$builder->getQuery()->whereNull('updated_at');
		$builder->getQuery()->whereNull('deleted_at');
		$builder->getQuery()->whereNull('foo_bar');

		$builder->withTrashed();

		$this->assertEquals(2, count($builder->getQuery()->wheres));
	}


	public function testPaginateMethod()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('get'), array($this->getMockQuery()));
		$model = $this->getMockModel();
		$model->shouldReceive('getPerPage')->once()->andReturn(15);
		$builder->setModel($model);
		$query = $builder->getQuery();
		$query->shouldReceive('getPaginationCount')->once()->andReturn(10);
		$paginator = m::mock('stdClass');
		$paginator->shouldReceive('getCurrentPage')->once()->andReturn(1);
		$conn = m::mock('stdClass');
		$conn->shouldReceive('getPaginator')->once()->andReturn($paginator);
		$query->shouldReceive('getConnection')->once()->andReturn($conn);
		$query->shouldReceive('forPage')->once()->with(1, 15);
		$collection = m::mock('stdClass');
		$collection->shouldReceive('all')->once()->andReturn(array('results'));
		$builder->expects($this->once())->method('get')->with($this->equalTo(array('*')))->will($this->returnValue($collection));
		$paginator->shouldReceive('make')->once()->with(array('results'), 10, 15)->andReturn(array('results'));

		$this->assertEquals(array('results'), $builder->paginate());
	}


	public function testPaginateMethodWithGroupedQuery()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('get'), array($this->getMockQuery()));
		$model = $this->getMockModel();
		$model->shouldReceive('getPerPage')->once()->andReturn(2);
		$query = $this->getMock('Illuminate\Database\Query\Builder', array('from', 'getConnection'), array(
			m::mock('Illuminate\Database\ConnectionInterface'),
			m::mock('Illuminate\Database\Query\Grammars\Grammar'),
			m::mock('Illuminate\Database\Query\Processors\Processor'),
		));
		$builder->setQuery($query);
		$query->expects($this->once())->method('from')->will($this->returnValue('foo_table'));
		$builder->setModel($model);
		$conn = m::mock('stdClass');
		$paginator = m::mock('stdClass');
		$paginator->shouldReceive('getCurrentPage')->once()->andReturn(2);
		$conn->shouldReceive('getPaginator')->once()->andReturn($paginator);
		$query->expects($this->once())->method('getConnection')->will($this->returnValue($conn));
		$collection = m::mock('stdClass');
		$collection->shouldReceive('all')->once()->andReturn(array('foo', 'bar', 'baz'));
		$builder->expects($this->once())->method('get')->with($this->equalTo(array('*')))->will($this->returnValue($collection));
		$paginator->shouldReceive('make')->once()->with(array('baz'), 3, 2)->andReturn(array('results'));

		$this->assertEquals(array('results'), $builder->groupBy('foo')->paginate());
	}


	public function testGetModelsProperlyHydratesModels()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('get'), array($this->getMockQuery()));
		$records[] = array('name' => 'taylor', 'age' => 26);
		$records[] = array('name' => 'dayle', 'age' => 28);
		$builder->getQuery()->shouldReceive('get')->once()->with(array('foo'))->andReturn($records);
		$model = m::mock('Illuminate\Database\Eloquent\Model[getTable,getConnectionName,newInstance]');
		$model->shouldReceive('getTable')->once()->andReturn('foobars');
		$builder->getQuery()->shouldReceive('from')->once()->with('foobars');
		$builder->setModel($model);
		$model->shouldReceive('getConnectionName')->once()->andReturn('foo_connection');
		$model->shouldReceive('newInstance')->andReturnUsing(function() { return new EloquentBuilderTestModelStub; });
		$models = $builder->getModels(array('foo'));

		$this->assertEquals('taylor', $models[0]->name);
		$this->assertEquals($models[0]->getAttributes(), $models[0]->getOriginal());
		$this->assertEquals('dayle', $models[1]->name);
		$this->assertEquals($models[1]->getAttributes(), $models[1]->getOriginal());
		$this->assertEquals('foo_connection', $models[0]->getConnectionName());
		$this->assertEquals('foo_connection', $models[1]->getConnectionName());
	}


	public function testEagerLoadRelationsLoadTopLevelRelationships()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('loadRelation'), array($this->getMockQuery()));
		$nop1 = function() {};
		$nop2 = function() {};
		$builder->setEagerLoads(array('foo' => $nop1, 'foo.bar' => $nop2));
		$builder->expects($this->once())->method('loadRelation')->with($this->equalTo(array('models')), $this->equalTo('foo'), $this->equalTo($nop1))->will($this->returnValue(array('foo')));
		$results = $builder->eagerLoadRelations(array('models'));

		$this->assertEquals(array('foo'), $results);
	}


	public function testRelationshipEagerLoadProcess()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('getRelation'), array($this->getMockQuery()));
		$builder->setEagerLoads(array('orders' => function($query) { $_SERVER['__eloquent.constrain'] = $query; }));
		$relation = m::mock('stdClass');
		$relation->shouldReceive('addEagerConstraints')->once()->with(array('models'));
		$relation->shouldReceive('initRelation')->once()->with(array('models'), 'orders')->andReturn(array('models'));
		$relation->shouldReceive('get')->once()->andReturn(array('results'));
		$relation->shouldReceive('match')->once()->with(array('models'), array('results'), 'orders')->andReturn(array('models.matched'));
		$builder->expects($this->once())->method('getRelation')->with($this->equalTo('orders'))->will($this->returnValue($relation));
		$results = $builder->eagerLoadRelations(array('models'));

		$this->assertEquals(array('models.matched'), $results);
		$this->assertEquals($relation, $_SERVER['__eloquent.constrain']);
		unset($_SERVER['__eloquent.constrain']);
	}


	public function testGetRelationProperlySetsNestedRelationships()
	{
		$builder = $this->getBuilder();
		$model = $this->getMockModel();
		$builder->setModel($model);
		$model->shouldReceive('orders')->once()->andReturn($relation = m::mock('stdClass'));
		$relationQuery = m::mock('stdClass');
		$relation->shouldReceive('getQuery')->andReturn($relationQuery);
		$relationQuery->shouldReceive('with')->once()->with(array('lines' => null, 'lines.details' => null));
		$builder->setEagerLoads(array('orders' => null, 'orders.lines' => null, 'orders.lines.details' => null));

		$relation = $builder->getRelation('orders');
	}


	public function testEagerLoadParsingSetsProperRelationships()
	{
		$builder = $this->getBuilder();
		$builder->with(array('orders', 'orders.lines'));
		$eagers = $builder->getEagerLoads();

		$this->assertEquals(array('orders', 'orders.lines'), array_keys($eagers));
		$this->assertInstanceOf('Closure', $eagers['orders']);
		$this->assertInstanceOf('Closure', $eagers['orders.lines']);

		$builder = $this->getBuilder();
		$builder->with('orders', 'orders.lines');
		$eagers = $builder->getEagerLoads();

		$this->assertEquals(array('orders', 'orders.lines'), array_keys($eagers));
		$this->assertInstanceOf('Closure', $eagers['orders']);
		$this->assertInstanceOf('Closure', $eagers['orders.lines']);

		$builder = $this->getBuilder();
		$builder->with(array('orders.lines'));
		$eagers = $builder->getEagerLoads();

		$this->assertEquals(array('orders', 'orders.lines'), array_keys($eagers));
		$this->assertInstanceOf('Closure', $eagers['orders']);
		$this->assertInstanceOf('Closure', $eagers['orders.lines']);

		$builder = $this->getBuilder();
		$builder->with(array('orders' => function() { return 'foo'; }));
		$eagers = $builder->getEagerLoads();

		$this->assertEquals('foo', $eagers['orders']());

		$builder = $this->getBuilder();
		$builder->with(array('orders.lines' => function() { return 'foo'; }));
		$eagers = $builder->getEagerLoads();

		$this->assertInstanceOf('Closure', $eagers['orders']);
		$this->assertNull($eagers['orders']());
		$this->assertEquals('foo', $eagers['orders.lines']());
	}


	public function testQueryPassThru()
	{
		$builder = $this->getBuilder();
		$builder->getQuery()->shouldReceive('foobar')->once()->andReturn('foo');

		$this->assertInstanceOf('Illuminate\Database\Eloquent\Builder', $builder->foobar());

		$builder = $this->getBuilder();
		$builder->getQuery()->shouldReceive('insert')->once()->with(array('bar'))->andReturn('foo');

		$this->assertEquals('foo', $builder->insert(array('bar')));
	}


	public function testQueryScopes()
	{
		$builder = $this->getBuilder();
		$builder->getQuery()->shouldReceive('from');
		$builder->getQuery()->shouldReceive('where')->once()->with('foo', 'bar', null, 'and');
		$builder->setModel($model = new EloquentBuilderTestScopeStub);
		$result = $builder->approved();

		$this->assertEquals($builder, $result);
	}


	public function testNestedWhere()
	{
		$nestedQuery = $this->getMockEloquentBuilder();
		$nestedRawQuery = $this->getMockQueryBuilder();
		$nestedQuery->shouldReceive('getQuery')->once()->andReturn($nestedRawQuery);
		$model = $this->getMockModel()->makePartial();
		$model->shouldReceive('newQuery')->once()->andReturn($nestedQuery);
		$builder = $this->getBuilder();
		$builder->getQuery()->shouldReceive('from');
		$builder->setModel($model);
		$builder->getQuery()->shouldReceive('addNestedWhereQuery')->once()->with($nestedRawQuery, 'and');
		$nestedQuery->shouldReceive('foo')->once();

		$result = $builder->where(function($query) { $query->foo(); });
		$this->assertEquals($builder, $result);
	}


	protected function getBuilder()
	{
		return new Builder($this->getMockQuery());
	}


	protected function getMockQuery()
	{
		$query = m::mock('Illuminate\Database\Query\Builder');
		$query->shouldReceive('from')->with('foo_table');
		return $query;
	}

	protected function getMockModel()
	{
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('getTable')->andReturn('foo_table');
		return $model;
	}


	protected function getMockModel()
	{
		return m::mock('Illuminate\Database\Eloquent\Model');
	}


	protected function getMockEloquentBuilder()
	{
		return m::mock('Illuminate\Database\Eloquent\Builder');
	}


	protected function getMockQueryBuilder()
	{
		return m::mock('Illuminate\Database\Query\Builder');
	}

}

class EloquentBuilderTestModelStub extends Illuminate\Database\Eloquent\Model {}
class EloquentBuilderTestScopeStub extends Illuminate\Database\Eloquent\Model {
	public function scopeApproved($query)
	{
		$query->where('foo', 'bar');
	}
}
class EloquentBuilderTestListsStub {
	protected $attributes;
	public function __construct($attributes)
	{
		$this->attributes = $attributes;
	}
	public function __get($key)
	{
		return 'foo_' . $this->attributes[$key];
	}
}
