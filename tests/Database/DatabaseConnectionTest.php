<?php

use Mockery as m;

class DatabaseConnectionTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testSettingDefaultCallsGetDefaultGrammar()
	{
		$connection = $this->getMockConnection();
		$mock = m::mock('StdClass');
		$connection->expects($this->once())->method('getDefaultQueryGrammar')->will($this->returnValue($mock));
		$connection->useDefaultQueryGrammar();
		$this->assertEquals($mock, $connection->getQueryGrammar());
	}


	public function testSettingDefaultCallsGetDefaultPostProcessor()
	{
		$connection = $this->getMockConnection();
		$mock = m::mock('StdClass');
		$connection->expects($this->once())->method('getDefaultPostProcessor')->will($this->returnValue($mock));
		$connection->useDefaultPostProcessor();
		$this->assertEquals($mock, $connection->getPostProcessor());
	}


	public function testSelectOneCallsSelectAndReturnsSingleResult()
	{
		$connection = $this->getMockConnection(array('select'));
		$connection->expects($this->once())->method('select')->with('foo', array('bar' => 'baz'))->will($this->returnValue(array('foo')));
		$this->assertEquals('foo', $connection->selectOne('foo', array('bar' => 'baz')));
	}


	public function testSelectProperlyCallsPDO()
	{
		$pdo = $this->getMock('DatabaseConnectionTestMockPDO', array('prepare'));
		$statement = $this->getMock('PDOStatement', array('execute', 'fetchAll'));
		$statement->expects($this->once())->method('execute')->with($this->equalTo(array('foo' => 'bar')));
		$statement->expects($this->once())->method('fetchAll')->will($this->returnValue(array('boom')));
		$pdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
		$mock = $this->getMockConnection(array('prepareBindings'), $pdo);
		$mock->expects($this->once())->method('prepareBindings')->with($this->equalTo(array('foo' => 'bar')))->will($this->returnValue(array('foo' => 'bar')));
		$results = $mock->select('foo', array('foo' => 'bar'));
		$this->assertEquals(array('boom'), $results);
		$log = $mock->getQueryLog();
		$this->assertEquals('foo', $log[0]['query']);
		$this->assertEquals(array('foo' => 'bar'), $log[0]['bindings']);
		$this->assertTrue(is_numeric($log[0]['time']));
	}


	public function testInsertCallsTheStatementMethod()
	{
		$connection = $this->getMockConnection(array('statement'));
		$connection->expects($this->once())->method('statement')->with($this->equalTo('foo'), $this->equalTo(array('bar')))->will($this->returnValue('baz'));
		$results = $connection->insert('foo', array('bar'));
		$this->assertEquals('baz', $results);
	}


	public function testUpdateCallsTheAffectingStatementMethod()
	{
		$connection = $this->getMockConnection(array('affectingStatement'));
		$connection->expects($this->once())->method('affectingStatement')->with($this->equalTo('foo'), $this->equalTo(array('bar')))->will($this->returnValue('baz'));
		$results = $connection->update('foo', array('bar'));
		$this->assertEquals('baz', $results);
	}


	public function testDeleteCallsTheAffectingStatementMethod()
	{
		$connection = $this->getMockConnection(array('affectingStatement'));
		$connection->expects($this->once())->method('affectingStatement')->with($this->equalTo('foo'), $this->equalTo(array('bar')))->will($this->returnValue('baz'));
		$results = $connection->delete('foo', array('bar'));
		$this->assertEquals('baz', $results);
	}


	public function testStatementProperlyCallsPDO()
	{
		$pdo = $this->getMock('DatabaseConnectionTestMockPDO', array('prepare'));
		$statement = $this->getMock('PDOStatement', array('execute'));
		$statement->expects($this->once())->method('execute')->with($this->equalTo(array('bar')))->will($this->returnValue('foo'));
		$pdo->expects($this->once())->method('prepare')->with($this->equalTo('foo'))->will($this->returnValue($statement));
		$mock = $this->getMockConnection(array('prepareBindings'), $pdo);
		$mock->expects($this->once())->method('prepareBindings')->with($this->equalTo(array('bar')))->will($this->returnValue(array('bar')));
		$results = $mock->statement('foo', array('bar'));
		$this->assertEquals('foo', $results);
		$log = $mock->getQueryLog();
		$this->assertEquals('foo', $log[0]['query']);
		$this->assertEquals(array('bar'), $log[0]['bindings']);
		$this->assertTrue(is_numeric($log[0]['time']));
	}


	public function testAffectingStatementProperlyCallsPDO()
	{
		$pdo = $this->getMock('DatabaseConnectionTestMockPDO', array('prepare'));
		$statement = $this->getMock('PDOStatement', array('execute', 'rowCount'));
		$statement->expects($this->once())->method('execute')->with($this->equalTo(array('foo' => 'bar')));
		$statement->expects($this->once())->method('rowCount')->will($this->returnValue(array('boom')));
		$pdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
		$mock = $this->getMockConnection(array('prepareBindings'), $pdo);
		$mock->expects($this->once())->method('prepareBindings')->with($this->equalTo(array('foo' => 'bar')))->will($this->returnValue(array('foo' => 'bar')));
		$results = $mock->update('foo', array('foo' => 'bar'));
		$this->assertEquals(array('boom'), $results);
		$log = $mock->getQueryLog();
		$this->assertEquals('foo', $log[0]['query']);
		$this->assertEquals(array('foo' => 'bar'), $log[0]['bindings']);
		$this->assertTrue(is_numeric($log[0]['time']));
	}


	public function testTransactionMethodRunsSuccessfully()
	{
		$pdo = $this->getMock('DatabaseConnectionTestMockPDO', array('beginTransaction', 'commit'));
		$mock = $this->getMockConnection(array(), $pdo);
		$pdo->expects($this->once())->method('beginTransaction');
		$pdo->expects($this->once())->method('commit');
		$result = $mock->transaction(function($db) { return $db; });
		$this->assertEquals($mock, $result);
	}


	public function testTransactionMethodRollsbackAndThrows()
	{
		$pdo = $this->getMock('DatabaseConnectionTestMockPDO', array('beginTransaction', 'commit', 'rollBack'));
		$mock = $this->getMockConnection(array(), $pdo);
		$pdo->expects($this->once())->method('beginTransaction');
		$pdo->expects($this->once())->method('rollBack');
		$pdo->expects($this->never())->method('commit');
		try
		{
			$mock->transaction(function() { throw new Exception('foo'); });
		}
		catch (Exception $e)
		{
			$this->assertEquals('foo', $e->getMessage());
		}
	}


	public function testFromCreatesNewQueryBuilder()
	{
		$conn = $this->getMockConnection();
		$conn->setQueryGrammar(m::mock('Illuminate\Database\Query\Grammars\Grammar'));
		$conn->setPostProcessor(m::mock('Illuminate\Database\Query\Processors\Processor'));
		$builder = $conn->table('users');
		$this->assertInstanceOf('Illuminate\Database\Query\Builder', $builder);
		$this->assertEquals('users', $builder->from);
	}


	public function testPrepareBindings()
	{
		$date = m::mock('DateTime');
		$date->shouldReceive('format')->once()->with('foo')->andReturn('bar');
		$bindings = array('test' => $date);
		$conn = $this->getMockConnection();
		$grammar = m::mock('Illuminate\Database\Query\Grammars\Grammar');
		$grammar->shouldReceive('getDateFormat')->once()->andReturn('foo');
		$conn->setQueryGrammar($grammar);
		$result = $conn->prepareBindings($bindings);
		$this->assertEquals(array('test' => 'bar'), $result);
	}


	public function testLogQueryFiresEventsIfSet()
	{
		$connection = $this->getMockConnection();
		$connection->logQuery('foo', array(), time());
		$connection->setEventDispatcher($events = m::mock('Illuminate\Events\Dispatcher'));
		$events->shouldReceive('fire')->once()->with('illuminate.query', array('foo', array(), null));
		$connection->logQuery('foo', array(), null);
	}


	public function testPretendOnlyLogsQueries()
	{
		$connection = $this->getMockConnection();
		$queries = $connection->pretend(function($connection)
		{
			$connection->select('foo bar', array('baz'));
		});
		$this->assertEquals('foo bar', $queries[0]['query']);
		$this->assertEquals(array('baz'), $queries[0]['bindings']);
	}


	public function testSchemaBuilderCanBeCreated()
	{
		$connection = $this->getMockConnection();
		$schema = $connection->getSchemaBuilder();
		$this->assertInstanceOf('Illuminate\Database\Schema\Builder', $schema);
		$this->assertTrue($connection === $schema->getConnection());
	}


	protected function getMockConnection($methods = array(), $pdo = null)
	{
		$pdo = $pdo ?: new DatabaseConnectionTestMockPDO;
		$defaults = array('getDefaultQueryGrammar', 'getDefaultPostProcessor', 'getDefaultSchemaGrammar');
		return $this->getMock('Illuminate\Database\Connection', array_merge($defaults, $methods), array($pdo));
	}

}

class DatabaseConnectionTestMockPDO extends PDO { public function __construct() {} }