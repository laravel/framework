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
		$query = m::mock('Illuminate\Database\Query\Builder');
		$query->shouldReceive('where')->once()->with('foo', '=', 'bar');
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('first'), array($query));
 		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('getKeyName')->once()->andReturn('foo');
		$model->shouldReceive('getTable')->once()->andReturn('table');
		$query->shouldReceive('from')->once()->with('table');
		$builder->setModel($model);
		$builder->expects($this->once())->method('first')->with($this->equalTo(array('column')))->will($this->returnValue('baz'));
		$result = $builder->find('bar', array('column'));
		$this->assertEquals('baz', $result);
	}


	public function testFirstMethod()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('get', 'take'), $this->getMocks());
		$collection = m::mock('stdClass');
		$collection->shouldReceive('first')->once()->andReturn('bar');
		$builder->expects($this->once())->method('take')->with($this->equalTo(1))->will($this->returnValue($builder));
		$builder->expects($this->once())->method('get')->with($this->equalTo(array('*')))->will($this->returnValue($collection));

		$result = $builder->first();
		$this->assertEquals('bar', $result);
	}


	public function testGetMethodLoadsModelsAndHydratesEagerRelations()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('getModels', 'eagerLoadRelations'), $this->getMocks());
		$builder->expects($this->once())->method('getModels')->with($this->equalTo(array('foo')))->will($this->returnValue(array('bar')));
		$builder->expects($this->once())->method('eagerLoadRelations')->with($this->equalTo(array('bar')))->will($this->returnValue(array('bar', 'baz')));
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('newCollection')->with(array('bar', 'baz'))->andReturn(new Illuminate\Database\Eloquent\Collection(array('bar', 'baz')));
		$model->shouldReceive('getTable')->once()->andReturn('foo');
		$builder->getQuery()->shouldReceive('from')->once()->with('foo');
		$builder->setModel($model);
		$results = $builder->get(array('foo'));

		$this->assertEquals(array('bar', 'baz'), $results->all());
	}


	public function testGetMethodDoesntHydrateEagerRelationsWhenNoResultsAreReturned()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('getModels', 'eagerLoadRelations'), $this->getMocks());
		$builder->expects($this->once())->method('getModels')->with($this->equalTo(array('foo')))->will($this->returnValue(array()));
		$builder->expects($this->never())->method('eagerLoadRelations');
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('newCollection')->with(array())->andReturn(new Illuminate\Database\Eloquent\Collection(array()));
		$model->shouldReceive('getTable')->once()->andReturn('foo');
		$builder->getQuery()->shouldReceive('from')->once()->with('foo');
		$builder->setModel($model);
		$results = $builder->get(array('foo'));

		$this->assertEquals(array(), $results->all());
	}


	public function testPaginateMethod()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('get'), $this->getMocks());
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('getPerPage')->once()->andReturn(15);
		$model->shouldReceive('getTable')->once()->andReturn('foo_table');
		$query = $builder->getQuery();
		$query->shouldReceive('from')->once()->with('foo_table');
		$builder->setModel($model);
		$query->shouldReceive('getPaginationCount')->once()->andReturn(10);
		$conn = m::mock('stdClass');
		$paginator = m::mock('stdClass');
		$paginator->shouldReceive('getCurrentPage')->once()->andReturn(1);
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
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('get'), $this->getMocks());
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('getPerPage')->once()->andReturn(2);
		$model->shouldReceive('getTable')->once()->andReturn('foo_table');
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
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('get'), $this->getMocks());
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
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('loadRelation'), $this->getMocks());
		$builder->setEagerLoads(array('foo' => function() {}, 'foo.bar' => function() {}));
		$builder->expects($this->once())->method('loadRelation')->with($this->equalTo(array('models')), $this->equalTo('foo'), $this->equalTo(function() {}))->will($this->returnValue(array('foo')));
		$results = $builder->eagerLoadRelations(array('models'));

		$this->assertEquals(array('foo'), $results);
	}


	public function testRelationshipEagerLoadProcess()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('getRelation'), $this->getMocks());
		$builder->setEagerLoads(array('orders' => function($query) { $_SERVER['__eloquent.constrain'] = $query; }));
		$relation = m::mock('stdClass');
		$relation->shouldReceive('getAndResetWheres')->once()->andReturn(array(array('wheres'), array('bindings')));
		$relation->shouldReceive('addEagerConstraints')->once()->with(array('models'));
		$relation->shouldReceive('mergeWheres')->once()->with(array('wheres'), array('bindings'));
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
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$builder->getQuery()->shouldReceive('from')->once()->with('foo');
		$model->shouldReceive('getTable')->once()->andReturn('foo');
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
		$builder->getQuery()->shouldReceive('where')->once()->with('foo', 'bar');
		$builder->setModel($model = new EloquentBuilderTestScopeStub);
		$result = $builder->approved();

		$this->assertEquals($builder, $result);
	}


	protected function getBuilder()
	{
		return new Builder(m::mock('Illuminate\Database\Query\Builder'));
	}


	protected function getMocks()
	{
		return array(m::mock('Illuminate\Database\Query\Builder'));
	}

}

class EloquentBuilderTestModelStub extends Illuminate\Database\Eloquent\Model {}
class EloquentBuilderTestScopeStub extends Illuminate\Database\Eloquent\Model {
	public function scopeApproved($query)
	{
		$query->where('foo', 'bar');
	}
}