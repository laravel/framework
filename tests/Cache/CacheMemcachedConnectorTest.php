<?php
if (!class_exists('Memcached')) {
	class Memcached {
		const OPT_BINARY_PROTOCOL = 18;
	}
}

use Mockery as m;

class CacheMemcachedConnectorTest extends PHPUnit_Framework_TestCase {
	/**
	 * app mock
	 * @var array
	 */
	protected $app;


	public function setUp()
	{
		$this->app = [];
	}


	public function tearDown()
	{
		m::close();
	}


	public function testServersAreAddedCorrectly()
	{
		$connector = $this->getMock('Illuminate\Cache\MemcachedConnector', array('getMemcached'), array($this->app));
		$memcached = m::mock('stdClass');
		$memcached->shouldReceive('addServer')->once()->with('localhost', 11211, 100);
		$memcached->shouldReceive('getVersion')->once()->andReturn(true);
		$memcached->shouldReceive('setOptions')->never();
		$memcached->shouldReceive('setSaslAuthData')->never();
		$connector->expects($this->once())->method('getMemcached')->will($this->returnValue($memcached));
		$result = $connector->connect(array(array('host' => 'localhost', 'port' => 11211, 'weight' => 100)));

		$this->assertSame($result, $memcached);
	}


	/**
	 * @expectedException RuntimeException
	 */
	public function testExceptionThrownOnBadConnection()
	{
		$connector = $this->getMock('Illuminate\Cache\MemcachedConnector', array('getMemcached'), array($this->app));
		$memcached = m::mock('stdClass');
		$memcached->shouldReceive('addServer')->once()->with('localhost', 11211, 100);
		$memcached->shouldReceive('getVersion')->once()->andReturn(false);
		$connector->expects($this->once())->method('getMemcached')->will($this->returnValue($memcached));
		$result = $connector->connect(array(array('host' => 'localhost', 'port' => 11211, 'weight' => 100)));
	}

	/**
	 *
	 */
	public function testSetOptionToMemcached()
	{
		$app = [
			'config' => [
				'cache.memcached_options' => [
					Memcached::OPT_BINARY_PROTOCOL => true,
				],
			],
		];

		$connector = $this->getMock('Illuminate\Cache\MemcachedConnector', array('getMemcached'), array($app));
		$memcached = m::mock('stdClass');
		$memcached->shouldReceive('addServer')->once()->with('localhost', 11211, 100);
		$memcached->shouldReceive('getVersion')->once()->andReturn(true);
		$memcached->shouldReceive('setOptions')->once()->with([Memcached::OPT_BINARY_PROTOCOL => true]);
		$memcached->shouldReceive('setSaslAuthData')->never();
		$connector->expects($this->once())->method('getMemcached')->will($this->returnValue($memcached));
		$result = $connector->connect(array(array('host' => 'localhost', 'port' => 11211, 'weight' => 100)));
	}

	/**
	 *
	 */
	public function testSetAuthDataToMemcached()
	{
		$app = [
			'config' => [
				'cache.memcached_sasl.username' => 'user',
				'cache.memcached_sasl.password' => 'pass',
			],
		];

		$connector = $this->getMock('Illuminate\Cache\MemcachedConnector', array('getMemcached'), array($app));
		$memcached = m::mock('stdClass');
		$memcached->shouldReceive('addServer')->once()->with('localhost', 11211, 100);
		$memcached->shouldReceive('getVersion')->once()->andReturn(true);
		$memcached->shouldReceive('setOptions')->never();
		$memcached->shouldReceive('setSaslAuthData')->once()->with('user', 'pass');
		$connector->expects($this->once())->method('getMemcached')->will($this->returnValue($memcached));
		$result = $connector->connect(array(array('host' => 'localhost', 'port' => 11211, 'weight' => 100)));
	}
}
