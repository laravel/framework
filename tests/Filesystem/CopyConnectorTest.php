<?php

use Illuminate\Filesystem\Adapters\CopyConnector;

class CopyConnectorTest extends PHPUnit_Framework_TestCase {

	public function testConnectStandard()
	{
		$connector = new CopyConnector();

		$return = $connector->connect([
			'consumer-key'	=> 'your-consumer-key',
			'consumer-secret' => 'your-consumer-secret',
			'access-token'	=> 'your-access-token',
			'token-secret'	=> 'your-token-secret',
		]);

		$this->assertInstanceOf('League\Flysystem\Adapter\Copy', $return);
	}


	public function testConnectWithPrefix()
	{
		$connector = new CopyConnector();

		$return = $connector->connect([
			'consumer-key'	=> 'your-consumer-key',
			'consumer-secret' => 'your-consumer-secret',
			'access-token'	=> 'your-access-token',
			'token-secret'	=> 'your-token-secret',
			'prefix'		  => 'your-prefix',
		]);

		$this->assertInstanceOf('League\Flysystem\Adapter\Copy', $return);
	}


	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testConnectWithoutConsumerKey()
	{
		$connector = new CopyConnector();

		$connector->connect([
			'consumer-secret' => 'your-consumer-secret',
			'access-token'	=> 'your-access-token',
			'token-secret'	=> 'your-token-secret',
		]);
	}


	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testConnectWithoutConsumerSecret()
	{
		$connector = new CopyConnector();

		$connector->connect([
			'consumer-key'	=> 'your-consumer-key',
			'access-token'	=> 'your-access-token',
			'token-secret'	=> 'your-token-secret',
		]);
	}


	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testConnectWithoutAccessToken()
	{
		$connector = new CopyConnector();

		$connector->connect([
			'consumer-key'	=> 'your-consumer-key',
			'consumer-secret' => 'your-consumer-secret',
			'token-secret'	=> 'your-token-secret',
		]);
	}


	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testConnectWithoutAccessSecret()
	{
		$connector = new CopyConnector();

		$connector->connect([
			'consumer-key'	=> 'your-consumer-key',
			'consumer-secret' => 'your-consumer-secret',
			'access-token'	=> 'your-access-token',
		]);
	}

}
