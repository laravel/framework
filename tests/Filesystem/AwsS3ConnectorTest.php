<?php

use Illuminate\Filesystem\Adapters\AwsS3Connector;

class AwsS3ConnectorTest extends PHPUnit_Framework_TestCase {

	public function testConnectStandard()
	{
		$connector = new AwsS3Connector();

		$return = $connector->connect([
			'key'	=> 'your-key',
			'secret' => 'your-secret',
			'bucket' => 'your-bucket',
		]);

		$this->assertInstanceOf('League\Flysystem\Adapter\AwsS3', $return);
	}


	public function testConnectWithPrefix()
	{
		$connector = new AwsS3Connector();

		$return = $connector->connect([
			'key'	=> 'your-key',
			'secret' => 'your-secret',
			'bucket' => 'your-bucket',
			'prefix' => 'your-prefix',
		]);

		$this->assertInstanceOf('League\Flysystem\Adapter\AwsS3', $return);
	}


	public function testConnectWithRegion()
	{
		$connector = new AwsS3Connector();

		$return = $connector->connect(array(
			'key'    => 'your-key',
			'secret' => 'your-secret',
			'bucket' => 'your-bucket',
			'region' => 'eu-west-1',
		));

		$this->assertInstanceOf('League\Flysystem\Adapter\AwsS3', $return);
	}


	public function testConnectWithBaseUrl()
	{
		$connector = new AwsS3Connector();

		$return = $connector->connect(array(
			'key'      => 'your-key',
			'secret'   => 'your-secret',
			'bucket'   => 'your-bucket',
			'base_url' => 'your-url',
		));

		$this->assertInstanceOf('League\Flysystem\Adapter\AwsS3', $return);
	}


	public function testConnectWithEverything()
	{
		$connector = new AwsS3Connector();

		$return = $connector->connect(array(
			'key'      => 'your-key',
			'secret'   => 'your-secret',
			'bucket'   => 'your-bucket',
			'region'   => 'eu-west-1',
			'base_url' => 'your-url',
		));

		$this->assertInstanceOf('League\Flysystem\Adapter\AwsS3', $return);
	}


	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testConnectWithoutBucket()
	{
		$connector = new AwsS3Connector();

		$connector->connect(['key' => 'your-key', 'secret' => 'your-secret']);
	}


	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testConnectWithoutKey()
	{
		$connector = new AwsS3Connector();

		$connector->connect(['secret' => 'your-secret', 'bucket' => 'your-bucket']);
	}


	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testConnectWithoutSecret()
	{
		$connector = new AwsS3Connector();

		$connector->connect(['key' => 'your-key', 'bucket' => 'your-bucket']);
	}

}
