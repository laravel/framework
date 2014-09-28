<?php

use Illuminate\Filesystem\Adapters\SftpConnector;

class SftpConnectorTest extends PHPUnit_Framework_TestCase {

	public function testConnect()
	{
		$connector = new SftpConnector();

		$return = $connector->connect([
			'host' => 'sftp.example.com',
			'port' => 22,
			'username' => 'your-username',
			'password' => 'your-password',
		]);

		$this->assertInstanceOf('League\Flysystem\Adapter\Sftp', $return);
	}

}
