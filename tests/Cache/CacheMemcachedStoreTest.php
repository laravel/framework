<?php

class CacheMemcachedStoreTest extends PHPUnit_Framework_TestCase {

	public function testGetReturnsNullWhenNotFound()
	{
		$memcache = $this->getMock('Memcached', array('get'));
		$memcache->expects($this->once())->method('get')->with($this->equalTo('foobar'))->will($this->returnValue(null));
		$store = new Illuminate\Cache\MemcachedStore($memcache, 'foo');
		$this->assertNull($store->get('bar'));
	}


	public function testMemcacheValueIsReturned()
	{
		$memcache = $this->getMock('Memcached', array('get'));
		$memcache->expects($this->once())->method('get')->will($this->returnValue('bar'));
		$store = new Illuminate\Cache\MemcachedStore($memcache);
		$this->assertEquals('bar', $store->get('foo'));
	}


	public function testSetMethodProperlyCallsMemcache()
	{
		$memcache = $this->getMock('Memcached', array('set'));
		$memcache->expects($this->once())->method('set')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(60));
		$store = new Illuminate\Cache\MemcachedStore($memcache);
		$store->put('foo', 'bar', 1);
	}


	public function testStoreItemForeverProperlyCallsMemcached()
	{
		$memcache = $this->getMock('Memcached', array('set'));
		$memcache->expects($this->once())->method('set')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(0));
		$store = new Illuminate\Cache\MemcachedStore($memcache);
		$store->forever('foo', 'bar');
	}


	public function testForgetMethodProperlyCallsMemcache()
	{
		$memcache = $this->getMock('Memcached', array('delete'));
		$memcache->expects($this->once())->method('delete')->with($this->equalTo('foo'));
		$store = new Illuminate\Cache\MemcachedStore($memcache);
		$store->forget('foo');
	}

}