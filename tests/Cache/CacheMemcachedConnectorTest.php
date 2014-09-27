<?php

use Mockery as m;

class CacheMemcachedConnectorTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testServersAreAddedCorrectly()
	{
		$connector = $this->getMock('Illuminate\Cache\MemcachedConnector', ['getMemcached']);
		$memcached = m::mock('stdClass');
		$memcached->shouldReceive('addServer')->once()->with('localhost', 11211, 100);
		$memcached->shouldReceive('getVersion')->once()->andReturn(true);
		$connector->expects($this->once())->method('getMemcached')->will($this->returnValue($memcached));
		$result = $connector->connect([['host' => 'localhost', 'port' => 11211, 'weight' => 100]]);

		$this->assertSame($result, $memcached);
	}


	/**
	 * @expectedException RuntimeException
	 */
	public function testExceptionThrownOnBadConnection()
	{
		$connector = $this->getMock('Illuminate\Cache\MemcachedConnector', ['getMemcached']);
		$memcached = m::mock('stdClass');
		$memcached->shouldReceive('addServer')->once()->with('localhost', 11211, 100);
		$memcached->shouldReceive('getVersion')->once()->andReturn(false);
		$connector->expects($this->once())->method('getMemcached')->will($this->returnValue($memcached));
		$result = $connector->connect([['host' => 'localhost', 'port' => 11211, 'weight' => 100]]);
	}

}
