<?php

class CacheApcStoreTest extends PHPUnit_Framework_TestCase {

	public function testGetReturnsNullWhenNotFound()
	{
		$apc = $this->getMock('Illuminate\Cache\ApcWrapper', array('get'));
		$apc->expects($this->once())->method('get')->with($this->equalTo('foobar'))->will($this->returnValue(null));
		$store = new Illuminate\Cache\ApcStore($apc, 'foo');
		$this->assertNull($store->get('bar'));
	}


	public function testMemcacheValueIsReturned()
	{
		$apc = $this->getMock('Illuminate\Cache\ApcWrapper', array('get'));
		$apc->expects($this->once())->method('get')->will($this->returnValue('bar'));
		$store = new Illuminate\Cache\ApcStore($apc);
		$this->assertEquals('bar', $store->get('foo'));
	}


	public function testSetMethodProperlyCallsMemcache()
	{
		$apc = $this->getMock('Illuminate\Cache\ApcWrapper', array('put'));
		$apc->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(60));
		$store = new Illuminate\Cache\ApcStore($apc);
		$store->put('foo', 'bar', 1);
	}


	public function testStoreItemForeverProperlyCallsMemcached()
	{
		$apc = $this->getMock('Illuminate\Cache\ApcWrapper', array('put'));
		$apc->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(0));
		$store = new Illuminate\Cache\ApcStore($apc);
		$store->forever('foo', 'bar');
	}


	public function testForgetMethodProperlyCallsMemcache()
	{
		$apc = $this->getMock('Illuminate\Cache\ApcWrapper', array('delete'));
		$apc->expects($this->once())->method('delete')->with($this->equalTo('foo'));
		$store = new Illuminate\Cache\ApcStore($apc);
		$store->forget('foo');
	}

}