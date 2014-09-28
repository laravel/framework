<?php

use Illuminate\Filesystem\Adapters\WebDavConnector;

class WebDavConnectorTest extends PHPUnit_Framework_TestCase {

	public function testConnect()
	{
		$connector = new WebDavConnector();

		$return = $connector->connect([
			'baseUri'  => 'http://example.org/dav/',
			'userName' => 'your-username',
			'password' => 'your-password',
		]);

		$this->assertInstanceOf('League\Flysystem\Adapter\WebDav', $return);
	}

}
