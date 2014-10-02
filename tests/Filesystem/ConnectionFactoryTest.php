<?php

use Mockery as m;
use Illuminate\Filesystem\Adapters\ConnectionFactory;

class ConnectionFactoryTest extends PHPUnit_Framework_TestCase {

	public function testMake()
	{
		$factory = $this->getMockedFactory();

		$return = $factory->make(['driver' => 'local', 'path' => __DIR__, 'name' => 'local']);

		$this->assertInstanceOf('League\Flysystem\AdapterInterface', $return);
	}


	public function createDataProvider()
	{
		return [
			['awss3', 'AwsS3Connector'],
			['local', 'LocalConnector'],
			['null', 'NullConnector'],
			['rackspace', 'RackspaceConnector'],
		];
	}


	/**
	 * @dataProvider createDataProvider
	 */
	public function testCreateWorkingDriver($driver, $class)
	{
		$factory = $this->getConnectionFactory();

		$return = $factory->createConnector(['driver' => $driver]);

		$this->assertInstanceOf('Illuminate\Filesystem\Adapters\\'.$class, $return);
	}


	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testCreateEmptyDriverConnector()
	{
		$factory = $this->getConnectionFactory();

		$factory->createConnector([]);
	}


	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testCreateUnsupportedDriverConnector()
	{
		$factory = $this->getConnectionFactory();

		$factory->createConnector(['driver' => 'unsupported']);
	}


	protected function getConnectionFactory()
	{
		return new ConnectionFactory();
	}


	protected function getMockedFactory()
	{
		$mock = m::mock('Illuminate\Filesystem\Adapters\ConnectionFactory[createConnector]');

		$connector = m::mock('Illuminate\Filesystem\Adapters\LocalConnector');

		$connector->shouldReceive('connect')->once()
			->with(['name' => 'local', 'driver' => 'local', 'path' => __DIR__])
			->andReturn(m::mock('League\Flysystem\Adapter\Local'));

		$mock->shouldReceive('createConnector')->once()
			->with(['name' => 'local', 'driver' => 'local', 'path' => __DIR__])
			->andReturn($connector);

		return $mock;
	}

}
