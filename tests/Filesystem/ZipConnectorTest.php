<?php

use Illuminate\Filesystem\Adapters\ZipConnector;

class ZipConnectorTest extends PHPUnit_Framework_TestCase {

	public function testConnectStandard()
	{
		$connector = new ZipConnector();

		$return = $connector->connect(['path' => __DIR__.'\stubs\test.zip']);

		$this->assertInstanceOf('League\Flysystem\Adapter\Zip', $return);
	}


	public function testConnectWithPrefix()
	{
		$connector = new ZipConnector();

		$return = $connector->connect(['path' => __DIR__.'\stubs\test.zip', 'prefix' => 'your-prefix']);

		$this->assertInstanceOf('League\Flysystem\Adapter\Zip', $return);
	}


	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testConnectWithoutPath()
	{
		$connector = new ZipConnector();

		$connector->connect([]);
	}

}
