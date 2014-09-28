<?php

use Illuminate\Filesystem\Adapters\LocalConnector;

class LocalConnectorTest extends PHPUnit_Framework_TestCase {

	public function testConnectStandard()
	{
		$connector = new LocalConnector();

		$return = $connector->connect(['path' => __DIR__]);

		$this->assertInstanceOf('League\Flysystem\Adapter\Local', $return);
	}


	public function testConnectWithPrefix()
	{
		$connector = new LocalConnector();

		$return = $connector->connect(['path' => __DIR__, 'prefix' => 'your-prefix']);

		$this->assertInstanceOf('League\Flysystem\Adapter\Local', $return);
	}


	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testConnectWithoutPath()
	{
		$connector = new LocalConnector();

		$connector->connect([]);
	}

}
