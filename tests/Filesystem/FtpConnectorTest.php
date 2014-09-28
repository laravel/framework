<?php

use Illuminate\Filesystem\Adapters\FtpConnector;

class FtpConnectorTest extends PHPUnit_Framework_TestCase {

	public function testConnect()
	{
		if ( ! defined('FTP_BINARY')) {
			$this->markTestSkipped('The FTP_BINARY constant is not defined');
			return;
		}

		$connector = new FtpConnector();

		$return = $connector->connect([
			'host' => 'ftp.example.com',
			'port' => 21,
			'username' => 'your-username',
			'password' => 'your-password',
		]);

		$this->assertInstanceOf('League\Flysystem\Adapter\Ftp', $return);
	}

}
