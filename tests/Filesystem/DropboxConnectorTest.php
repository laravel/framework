<?php

use Illuminate\Filesystem\Adapters\DropboxConnector;

class DropboxConnectorTest extends PHPUnit_Framework_TestCase {

	public function testConnectStandard()
	{
		$connector = new DropboxConnector();

		$return = $connector->connect([
			'token'  => 'your-token',
			'app'	=> 'your-app',
		]);

		$this->assertInstanceOf('League\Flysystem\Adapter\Dropbox', $return);
	}


	public function testConnectWithPrefix()
	{
		$connector = new DropboxConnector();

		$return = $connector->connect([
			'token'  => 'your-token',
			'app'	=> 'your-app',
			'prefix' => 'your-prefix',
		]);

		$this->assertInstanceOf('League\Flysystem\Adapter\Dropbox', $return);
	}


	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testConnectWithoutToken()
	{
		$connector = new DropboxConnector();

		$connector->connect(['app' => 'your-app']);
	}


	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testConnectWithoutSecret()
	{
		$connector = new DropboxConnector();

		$connector->connect(['token' => 'your-token']);
	}

}
