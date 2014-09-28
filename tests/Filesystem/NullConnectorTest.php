<?php

use Illuminate\Filesystem\Adapters\NullConnector;

class NullConnectorTest extends PHPUnit_Framework_TestCase {

	public function testConnect()
	{
		$connector = new NullConnector();

		$return = $connector->connect([]);

		$this->assertInstanceOf('League\Flysystem\Adapter\NullAdapter', $return);
	}

}
