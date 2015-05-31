<?php

use Mockery as m;
use Illuminate\Container\Container;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Database\Connectors\SQLiteConnector;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Database\Connectors\SqlServerConnector;

class DatabaseConnectionFactoryPDOStub extends PDO {
	public function __construct() {}
}

class DatabaseConnectionFactoryTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testMakeCallsCreateConnection()
	{
		$factory = $this->getMock(ConnectionFactory::class, array('createConnector', 'createConnection'), array($container = m::mock(Container::class)));
		$container->shouldReceive('bound')->andReturn(false);
		$connector = m::mock('stdClass');
		$config = array('driver' => 'mysql', 'prefix' => 'prefix', 'database' => 'database', 'name' => 'foo');
		$pdo = new DatabaseConnectionFactoryPDOStub;
		$connector->shouldReceive('connect')->once()->with($config)->andReturn($pdo);
		$factory->expects($this->once())->method('createConnector')->with($config)->will($this->returnValue($connector));
		$mockConnection = m::mock('stdClass');
		$passedConfig = array_merge($config, array('name' => 'foo'));
		$factory->expects($this->once())->method('createConnection')->with($this->equalTo('mysql'), $this->equalTo($pdo), $this->equalTo('database'), $this->equalTo('prefix'), $this->equalTo($passedConfig))->will($this->returnValue($mockConnection));
		$connection = $factory->make($config, 'foo');

		$this->assertEquals($mockConnection, $connection);
	}


	public function testMakeCallsCreateConnectionForReadWrite()
	{
		$factory = $this->getMock(ConnectionFactory::class, array('createConnector', 'createConnection'), array($container = m::mock(Container::class)));
		$container->shouldReceive('bound')->andReturn(false);
		$connector = m::mock('stdClass');
		$config = array(
			'read' => array('database' => 'database'),
			'write' => array('database' => 'database'),
			'driver' => 'mysql', 'prefix' => 'prefix', 'name' => 'foo',
		);
		$expect = $config;
		unset($expect['read']);
		unset($expect['write']);
		$expect['database'] = 'database';
		$pdo = new DatabaseConnectionFactoryPDOStub;
		$connector->shouldReceive('connect')->twice()->with($expect)->andReturn($pdo);
		$factory->expects($this->exactly(2))->method('createConnector')->with($expect)->will($this->returnValue($connector));
		$mockConnection = m::mock('stdClass');
		$mockConnection->shouldReceive('setReadPdo')->once()->andReturn($mockConnection);
		$passedConfig = array_merge($expect, array('name' => 'foo'));
		$factory->expects($this->once())->method('createConnection')->with($this->equalTo('mysql'), $this->equalTo($pdo), $this->equalTo('database'), $this->equalTo('prefix'), $this->equalTo($passedConfig))->will($this->returnValue($mockConnection));
		$connection = $factory->make($config, 'foo');

		$this->assertEquals($mockConnection, $connection);
	}


	public function testMakeCanCallTheContainer()
	{
		$factory = $this->getMock(ConnectionFactory::class, array('createConnector'), array($container = m::mock(Container::class)));
		$container->shouldReceive('bound')->andReturn(true);
		$connector = m::mock('stdClass');
		$config = array('driver' => 'mysql', 'prefix' => 'prefix', 'database' => 'database', 'name' => 'foo');
		$pdo = new DatabaseConnectionFactoryPDOStub;
		$connector->shouldReceive('connect')->once()->with($config)->andReturn($pdo);
		$passedConfig = array_merge($config, array('name' => 'foo'));
		$factory->expects($this->once())->method('createConnector')->with($config)->will($this->returnValue($connector));
		$container->shouldReceive('make')->once()->with('db.connection.mysql', array($pdo, 'database', 'prefix', $passedConfig))->andReturn('foo');
		$connection = $factory->make($config, 'foo');

		$this->assertEquals('foo', $connection);
	}


	public function testProperInstancesAreReturnedForProperDrivers()
	{
		$factory = new ConnectionFactory($container = m::mock(Container::class));
		$container->shouldReceive('bound')->andReturn(false);
		$this->assertInstanceOf(MySqlConnector::class, $factory->createConnector(['driver' => 'mysql']));
		$this->assertInstanceOf(SQLiteConnector::class, $factory->createConnector(['driver' => 'sqlite']));
		$this->assertInstanceOf(PostgresConnector::class, $factory->createConnector(['driver' => 'pgsql']));
		$this->assertInstanceOf(SqlServerConnector::class, $factory->createConnector(['driver' => 'sqlsrv']));
	}


	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testIfDriverIsntSetExceptionIsThrown()
	{
		$factory = new ConnectionFactory($container = m::mock(Container::class));
		$factory->createConnector(array('foo'));
	}


	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testExceptionIsThrownOnUnsupportedDriver()
	{
		$factory = new ConnectionFactory($container = m::mock(Container::class));
		$container->shouldReceive('bound')->once()->andReturn(false);
		$factory->createConnector(array('driver' => 'foo'));
	}


	public function testCustomConnectorsCanBeResolvedViaContainer()
	{
		$factory = new ConnectionFactory($container = m::mock(Container::class));
		$container->shouldReceive('bound')->once()->with('db.connector.foo')->andReturn(true);
		$container->shouldReceive('make')->once()->with('db.connector.foo')->andReturn('connector');

		$this->assertEquals('connector', $factory->createConnector(array('driver' => 'foo')));
	}

}
