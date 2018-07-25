<?php

use Mockery as m;

class RemoteSecLibGatewayTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testHostAndPortSetCorrectly()
	{
		$gateway = $this->getGateway();
		$this->assertEquals('127.0.0.1', $gateway->getHost());
		$this->assertEquals(22, $gateway->getPort());
	}


	public function testConnectProperlyCallsLoginWithAuth()
	{
		$gateway = $this->getGateway();
		$gateway->shouldReceive('getNewKey')->andReturn($key = m::mock('StdClass'));
		$key->shouldReceive('setPassword')->once()->with('keyphrase');
		$key->shouldReceive('loadKey')->once()->with('keystuff');
		$gateway->getConnection()->shouldReceive('login')->with('taylor', $key);

		$gateway->connect('taylor');
	}


	public function testKeyTextCanBeSetManually()
	{
		$files = m::mock('Illuminate\Filesystem\Filesystem');
		$gateway = m::mock('Illuminate\Remote\SecLibGateway', array('127.0.0.1:22', array('username' => 'taylor', 'keytext' => 'keystuff'), $files))->makePartial();
		$gateway->shouldReceive('getConnection')->andReturn(m::mock('StdClass'));
		$gateway->shouldReceive('getNewKey')->andReturn($key = m::mock('StdClass'));
		$key->shouldReceive('setPassword')->once()->with(null);
		$key->shouldReceive('loadKey')->once()->with('keystuff');
		$gateway->getConnection()->shouldReceive('login')->with('taylor', $key);

		$gateway->connect('taylor');
	}


	public function getGateway()
	{
		$files = m::mock('Illuminate\Filesystem\Filesystem');
		$files->shouldReceive('get')->with('keypath')->andReturn('keystuff');
		$gateway = m::mock('Illuminate\Remote\SecLibGateway', array('127.0.0.1:22', array('username' => 'taylor', 'key' => 'keypath', 'keyphrase' => 'keyphrase'), $files))->makePartial();
		$gateway->shouldReceive('getConnection')->andReturn(m::mock('StdClass'));
		return $gateway;
	}

}
