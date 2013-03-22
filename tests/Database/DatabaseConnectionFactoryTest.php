<?php

use Mockery as m;

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
		$factory = $this->getMock('Illuminate\Database\Connectors\ConnectionFactory', array('createConnector', 'createConnection'));
		$connector = m::mock('stdClass');
		$config = array('driver' => 'mysql', 'prefix' => 'prefix', 'database' => 'database');
		$pdo = new DatabaseConnectionFactoryPDOStub;
		$connector->shouldReceive('connect')->once()->with($config)->andReturn($pdo);
		$factory->expects($this->once())->method('createConnector')->with($config)->will($this->returnValue($connector));
		$mockConnection = m::mock('stdClass');
		$passedConfig = array_merge($config, array('name' => 'foo'));
		$factory->expects($this->once())->method('createConnection')->with($this->equalTo('mysql'), $this->equalTo($pdo), $this->equalTo('database'), $this->equalTo('prefix'), $this->equalTo($passedConfig))->will($this->returnValue($mockConnection));
		$connection = $factory->make($config, 'foo');

		$this->assertEquals($mockConnection, $connection);
	}


	public function testProperInstancesAreReturnedForProperDrivers()
	{
		$factory = new Illuminate\Database\Connectors\ConnectionFactory;
		$this->assertInstanceOf('Illuminate\Database\Connectors\MySqlConnector', $factory->createConnector(array('driver' => 'mysql')));
		$this->assertInstanceOf('Illuminate\Database\Connectors\PostgresConnector', $factory->createConnector(array('driver' => 'pgsql')));
		$this->assertInstanceOf('Illuminate\Database\Connectors\SQLiteConnector', $factory->createConnector(array('driver' => 'sqlite')));
		$this->assertInstanceOf('Illuminate\Database\Connectors\SqlServerConnector', $factory->createConnector(array('driver' => 'sqlsrv')));
	}


	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testIfDriverIsntSetExceptionIsThrown()
	{
		$factory = new Illuminate\Database\Connectors\ConnectionFactory;
		$factory->createConnector(array('foo'));
	}


	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testExceptionIsThrownOnUnsupportedDriver()
	{
		$factory = new Illuminate\Database\Connectors\ConnectionFactory;
		$factory->createConnector(array('driver' => 'foo'));
	}

}