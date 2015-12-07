<?php

use Mockery as m;

class DatabaseConnectionFactoryPDOStub extends PDO
{
    public function __construct()
    {
    }
}

class DatabaseConnectionFactoryTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testMakeCallsCreateConnection()
    {
        $factory = $this->getMock('Illuminate\Database\Connectors\ConnectionFactory', ['createConnector', 'createConnection'], [$container = m::mock('Illuminate\Container\Container')]);
        $container->shouldReceive('bound')->andReturn(false);
        $connector = m::mock('stdClass');
        $config = ['driver' => 'mysql', 'prefix' => 'prefix', 'database' => 'database', 'name' => 'foo'];
        $pdo = new DatabaseConnectionFactoryPDOStub;
        $connector->shouldReceive('connect')->once()->with($config)->andReturn($pdo);
        $factory->expects($this->once())->method('createConnector')->with($config)->will($this->returnValue($connector));
        $mockConnection = m::mock('stdClass');
        $passedConfig = array_merge($config, ['name' => 'foo']);
        $factory->expects($this->once())->method('createConnection')->with($this->equalTo('mysql'), $this->equalTo($pdo), $this->equalTo('database'), $this->equalTo('prefix'), $this->equalTo($passedConfig))->will($this->returnValue($mockConnection));
        $connection = $factory->make($config, 'foo');

        $this->assertEquals($mockConnection, $connection);
    }

    public function testMakeCallsCreateConnectionForReadWrite()
    {
        $factory = $this->getMock('Illuminate\Database\Connectors\ConnectionFactory', ['createConnector', 'createConnection'], [$container = m::mock('Illuminate\Container\Container')]);
        $container->shouldReceive('bound')->andReturn(false);
        $connector = m::mock('stdClass');
        $config = [
            'read' => ['database' => 'database'],
            'write' => ['database' => 'database'],
            'driver' => 'mysql', 'prefix' => 'prefix', 'name' => 'foo',
        ];
        $expect = $config;
        unset($expect['read']);
        unset($expect['write']);
        $expect['database'] = 'database';
        $pdo = new DatabaseConnectionFactoryPDOStub;
        $connector->shouldReceive('connect')->twice()->with($expect)->andReturn($pdo);
        $factory->expects($this->exactly(2))->method('createConnector')->with($expect)->will($this->returnValue($connector));
        $mockConnection = m::mock('stdClass');
        $mockConnection->shouldReceive('setReadPdo')->once()->andReturn($mockConnection);
        $passedConfig = array_merge($expect, ['name' => 'foo']);
        $factory->expects($this->once())->method('createConnection')->with($this->equalTo('mysql'), $this->equalTo($pdo), $this->equalTo('database'), $this->equalTo('prefix'), $this->equalTo($passedConfig))->will($this->returnValue($mockConnection));
        $connection = $factory->make($config, 'foo');

        $this->assertEquals($mockConnection, $connection);
    }

    public function testMakeCanCallTheContainer()
    {
        $factory = $this->getMock('Illuminate\Database\Connectors\ConnectionFactory', ['createConnector'], [$container = m::mock('Illuminate\Container\Container')]);
        $container->shouldReceive('bound')->andReturn(true);
        $connector = m::mock('stdClass');
        $config = ['driver' => 'mysql', 'prefix' => 'prefix', 'database' => 'database', 'name' => 'foo'];
        $pdo = new DatabaseConnectionFactoryPDOStub;
        $connector->shouldReceive('connect')->once()->with($config)->andReturn($pdo);
        $passedConfig = array_merge($config, ['name' => 'foo']);
        $factory->expects($this->once())->method('createConnector')->with($config)->will($this->returnValue($connector));
        $container->shouldReceive('make')->once()->with('db.connection.mysql', [$pdo, 'database', 'prefix', $passedConfig])->andReturn('foo');
        $connection = $factory->make($config, 'foo');

        $this->assertEquals('foo', $connection);
    }

    public function testProperInstancesAreReturnedForProperDrivers()
    {
        $factory = new Illuminate\Database\Connectors\ConnectionFactory($container = m::mock('Illuminate\Container\Container'));
        $container->shouldReceive('bound')->andReturn(false);
        $this->assertInstanceOf('Illuminate\Database\Connectors\MySqlConnector', $factory->createConnector(['driver' => 'mysql']));
        $this->assertInstanceOf('Illuminate\Database\Connectors\PostgresConnector', $factory->createConnector(['driver' => 'pgsql']));
        $this->assertInstanceOf('Illuminate\Database\Connectors\SQLiteConnector', $factory->createConnector(['driver' => 'sqlite']));
        $this->assertInstanceOf('Illuminate\Database\Connectors\SqlServerConnector', $factory->createConnector(['driver' => 'sqlsrv']));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testIfDriverIsntSetExceptionIsThrown()
    {
        $factory = new Illuminate\Database\Connectors\ConnectionFactory($container = m::mock('Illuminate\Container\Container'));
        $factory->createConnector(['foo']);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownOnUnsupportedDriver()
    {
        $factory = new Illuminate\Database\Connectors\ConnectionFactory($container = m::mock('Illuminate\Container\Container'));
        $container->shouldReceive('bound')->once()->andReturn(false);
        $factory->createConnector(['driver' => 'foo']);
    }

    public function testCustomConnectorsCanBeResolvedViaContainer()
    {
        $factory = new Illuminate\Database\Connectors\ConnectionFactory($container = m::mock('Illuminate\Container\Container'));
        $container->shouldReceive('bound')->once()->with('db.connector.foo')->andReturn(true);
        $container->shouldReceive('make')->once()->with('db.connector.foo')->andReturn('connector');

        $this->assertEquals('connector', $factory->createConnector(['driver' => 'foo']));
    }

    public function testGetAllDrivers()
    {
        $factory = new Illuminate\Database\Connectors\ConnectionFactory($container = m::mock('Illuminate\Container\Container'));
        $container->shouldReceive('bound')->andReturn(false);
        $allDrivers = $factory->getAllDrivers();
        $this->assertEquals(['mysql', 'pgsql', 'sqlite', 'sqlsrv'], $allDrivers);
    }

    public function testGetAvailableDrivers()
    {
        $factory = new Illuminate\Database\Connectors\ConnectionFactory($container = m::mock('Illuminate\Container\Container'));
        $container->shouldReceive('bound')->andReturn(false);
        $drivers = $factory->getAvailableDrivers();
        $allDrivers = $factory->getAllDrivers();
        $this->assertNotSame([], array_intersect_key($drivers, $allDrivers));
    }
}
