<?php

use Illuminate\Filesystem\Adapters\RackspaceConnector;

class RackspaceConnectorTest extends PHPUnit_Framework_TestCase {

	/**
	 * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
	 */
	public function testConnect()
	{
		$connector = new RackspaceConnector();

		$connector->connect([
			'endpoint'  => 'https://lon.identity.api.rackspacecloud.com/v2.0/',
			'username'  => 'your-username',
			'password'  => 'your-password',
			'container' => 'your-container',
		]);
	}


	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testConnectWithoutAuth()
	{
		$connector = new RackspaceConnector();

		$connector->connect([
			'endpoint'  => 'https://lon.identity.api.rackspacecloud.com/v2.0/',
			'container' => 'your-container',
		]);
	}


	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testConnectWithoutConfig()
	{
		$connector = new RackspaceConnector();

		$connector->connect([
			'username'  => 'your-username',
			'password'  => 'your-password',
		]);
	}

}
