<?php

use Mockery as m;

class CacheRedisStoreTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testGetReturnsNullWhenNotFound()
	{
		$redis = $this->getRedis();
		$redis->getRedis()->shouldReceive('get')->once()->with('prefix:foo')->andReturn(null);
		$this->assertNull($redis->get('foo'));
	}


	public function testRedisValueIsReturned()
	{
		$redis = $this->getRedis();
		$redis->getRedis()->shouldReceive('get')->once()->with('prefix:foo')->andReturn(serialize('foo'));
		$this->assertEquals('foo', $redis->get('foo'));
	}


	public function testRedisValueIsReturnedForNumerics()
	{
		$redis = $this->getRedis();
		$redis->getRedis()->shouldReceive('get')->once()->with('prefix:foo')->andReturn(1);
		$this->assertEquals(1, $redis->get('foo'));
	}


	public function testSetMethodProperlyCallsRedis()
	{
		$redis = $this->getRedis();
		$redis->getRedis()->shouldReceive('set')->once()->with('prefix:foo', serialize('foo'));
		$redis->getRedis()->shouldReceive('expire')->once()->with('prefix:foo', 60 * 60);
		$redis->put('foo', 'foo', 60);
	}


	public function testSetMethodProperlyCallsRedisForNumerics()
	{
		$redis = $this->getRedis();
		$redis->getRedis()->shouldReceive('set')->once()->with('prefix:foo', 1);
		$redis->getRedis()->shouldReceive('expire')->once()->with('prefix:foo', 60 * 60);
		$redis->put('foo', 1, 60);
	}


	public function testIncrementMethodProperlyCallsRedis()
	{
		$redis = $this->getRedis();
		$redis->getRedis()->shouldReceive('incrby')->once()->with('prefix:foo', 5);
		$redis->increment('foo', 5);
	}


	public function testDecrementMethodProperlyCallsRedis()
	{
		$redis = $this->getRedis();
		$redis->getRedis()->shouldReceive('decrby')->once()->with('prefix:foo', 5);
		$redis->decrement('foo', 5);
	}


	public function testStoreItemForeverProperlyCallsRedis()
	{
		$redis = $this->getRedis();
		$redis->getRedis()->shouldReceive('set')->once()->with('prefix:foo', serialize('foo'));
		$redis->forever('foo', 'foo', 60);
	}


	public function testForgetMethodProperlyCallsRedis()
	{
		$redis = $this->getRedis();
		$redis->getRedis()->shouldReceive('del')->once()->with('prefix:foo');
		$redis->forget('foo');
	}


	protected function getRedis()
	{
		return new Illuminate\Cache\RedisStore(m::mock('Illuminate\Redis\Database'), 'prefix');
	}

}