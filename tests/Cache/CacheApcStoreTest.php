<?php

class CacheApcStoreTest extends PHPUnit_Framework_TestCase {

	public function testGetReturnsNullWhenNotFound()
	{
		$apc = $this->getMock('Illuminate\Cache\ApcWrapper', ['get']);
		$apc->expects($this->once())->method('get')->with($this->equalTo('foobar'))->will($this->returnValue(null));
		$store = new Illuminate\Cache\ApcStore($apc, 'foo');
		$this->assertNull($store->get('bar'));
	}


	public function testAPCValueIsReturned()
	{
		$apc = $this->getMock('Illuminate\Cache\ApcWrapper', ['get']);
		$apc->expects($this->once())->method('get')->will($this->returnValue('bar'));
		$store = new Illuminate\Cache\ApcStore($apc);
		$this->assertEquals('bar', $store->get('foo'));
	}


	public function testSetMethodProperlyCallsAPC()
	{
		$apc = $this->getMock('Illuminate\Cache\ApcWrapper', ['put']);
		$apc->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(60));
		$store = new Illuminate\Cache\ApcStore($apc);
		$store->put('foo', 'bar', 1);
	}


	public function testIncrementMethodProperlyCallsAPC()
	{
		$apc = $this->getMock('Illuminate\Cache\ApcWrapper', ['increment']);
		$apc->expects($this->once())->method('increment')->with($this->equalTo('foo'), $this->equalTo(5));
		$store = new Illuminate\Cache\ApcStore($apc);
		$store->increment('foo', 5);
	}


	public function testDecrementMethodProperlyCallsAPC()
	{
		$apc = $this->getMock('Illuminate\Cache\ApcWrapper', ['decrement']);
		$apc->expects($this->once())->method('decrement')->with($this->equalTo('foo'), $this->equalTo(5));
		$store = new Illuminate\Cache\ApcStore($apc);
		$store->decrement('foo', 5);
	}


	public function testStoreItemForeverProperlyCallsAPC()
	{
		$apc = $this->getMock('Illuminate\Cache\ApcWrapper', ['put']);
		$apc->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(0));
		$store = new Illuminate\Cache\ApcStore($apc);
		$store->forever('foo', 'bar');
	}


	public function testForgetMethodProperlyCallsAPC()
	{
		$apc = $this->getMock('Illuminate\Cache\ApcWrapper', ['delete']);
		$apc->expects($this->once())->method('delete')->with($this->equalTo('foo'));
		$store = new Illuminate\Cache\ApcStore($apc);
		$store->forget('foo');
	}

}
