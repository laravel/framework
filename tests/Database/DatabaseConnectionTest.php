<?php

use Mockery as m;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

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
		$writePdo = $this->getMock('DatabaseConnectionTestMockPDO', array('prepare'));
		$writePdo->expects($this->never())->method('prepare');
		$statement = $this->getMock('PDOStatement', array('execute', 'fetchAll'));
		$statement->expects($this->once())->method('execute')->with($this->equalTo(array('foo' => 'bar')));
		$statement->expects($this->once())->method('fetchAll')->will($this->returnValue(array('boom')));
		$pdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
		$mock = $this->getMockConnection(array('prepareBindings'), $writePdo);
		$mock->setReadPdo($pdo);
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


	public function testBeganTransactionFiresEventsIfSet()
	{
		$pdo = $this->getMock('DatabaseConnectionTestMockPDO');
		$connection = $this->getMockConnection(array('getName'), $pdo);
		$connection->expects($this->once())->method('getName')->will($this->returnValue('name'));
		$connection->setEventDispatcher($events = m::mock(EventDispatcher::class));
		$events->shouldReceive('fire')->once()->with('connection.name.beganTransaction', $connection);
		$connection->beginTransaction();
	}


	public function testCommitedFiresEventsIfSet()
	{
		$pdo = $this->getMock('DatabaseConnectionTestMockPDO');
		$connection = $this->getMockConnection(array('getName'), $pdo);
		$connection->expects($this->once())->method('getName')->will($this->returnValue('name'));
		$connection->setEventDispatcher($events = m::mock(EventDispatcher::class));
		$events->shouldReceive('fire')->once()->with('connection.name.committed', $connection);
		$connection->commit();
	}


	public function testRollBackedFiresEventsIfSet()
	{
		$pdo = $this->getMock('DatabaseConnectionTestMockPDO');
		$connection = $this->getMockConnection(array('getName'), $pdo);
		$connection->expects($this->once())->method('getName')->will($this->returnValue('name'));
		$connection->setEventDispatcher($events = m::mock(EventDispatcher::class));
		$events->shouldReceive('fire')->once()->with('connection.name.rollingBack', $connection);
		$connection->rollBack();
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

	/**
	 * @expectedException RuntimeException
	 */
	public function testTransactionMethodDisallowPDOChanging()
	{
		$pdo = $this->getMock('DatabaseConnectionTestMockPDO', array('beginTransaction', 'commit', 'rollBack'));
		$pdo->expects($this->once())->method('beginTransaction');
		$pdo->expects($this->once())->method('rollBack');
		$pdo->expects($this->never())->method('commit');

		$mock = $this->getMockConnection(array(), $pdo);

		$mock->setReconnector(function ($connection)
		{
			$connection->setPDO(null);
		});

		$mock->transaction(function ($connection) { $connection->reconnect(); });
	}


	public function testFromCreatesNewQueryBuilder()
	{
		$conn = $this->getMockConnection();
		$conn->setQueryGrammar(m::mock(Grammar::class));
		$conn->setPostProcessor(m::mock(Processor::class));
		$builder = $conn->table('users');
		$this->assertInstanceOf(QueryBuilder::class, $builder);
		$this->assertEquals('users', $builder->from);
	}


	public function testPrepareBindings()
	{
		$date = m::mock('DateTime');
		$date->shouldReceive('format')->once()->with('foo')->andReturn('bar');
		$bindings = array('test' => $date);
		$conn = $this->getMockConnection();
		$grammar = m::mock(Grammar::class);
		$grammar->shouldReceive('getDateFormat')->once()->andReturn('foo');
		$conn->setQueryGrammar($grammar);
		$result = $conn->prepareBindings($bindings);
		$this->assertEquals(array('test' => 'bar'), $result);
	}


	public function testLogQueryFiresEventsIfSet()
	{
		$connection = $this->getMockConnection();
		$connection->logQuery('foo', array(), time());
		$connection->setEventDispatcher($events = m::mock(EventDispatcher::class));
		$events->shouldReceive('fire')->once()->with('illuminate.query', array('foo', array(), null, null));
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
		$this->assertInstanceOf(SchemaBuilder::class, $schema);
		$this->assertSame($connection, $schema->getConnection());
	}



	protected function getMockConnection($methods = array(), $pdo = null)
	{
		$pdo = $pdo ?: new DatabaseConnectionTestMockPDO;
		$defaults = array('getDefaultQueryGrammar', 'getDefaultPostProcessor', 'getDefaultSchemaGrammar');
		$connection = $this->getMock(Connection::class, array_merge($defaults, $methods), array($pdo));
		$connection->enableQueryLog();
		return $connection;
	}

}

class DatabaseConnectionTestMockPDO extends PDO { public function __construct() {} }
