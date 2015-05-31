<?php

use Illuminate\Cache\ApcStore;
use Illuminate\Cache\ApcWrapper;

class CacheApcStoreTest extends PHPUnit_Framework_TestCase {

	public function testGetReturnsNullWhenNotFound()
	{
		$apc = $this->getMock(ApcWrapper::class, array('get'));
		$apc->expects($this->once())->method('get')->with($this->equalTo('foobar'))->will($this->returnValue(null));
		$store = new ApcStore($apc, 'foo');
		$this->assertNull($store->get('bar'));
	}


	public function testAPCValueIsReturned()
	{
		$apc = $this->getMock(ApcWrapper::class, array('get'));
		$apc->expects($this->once())->method('get')->will($this->returnValue('bar'));
		$store = new ApcStore($apc);
		$this->assertEquals('bar', $store->get('foo'));
	}


	public function testSetMethodProperlyCallsAPC()
	{
		$apc = $this->getMock(ApcWrapper::class, array('put'));
		$apc->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(60));
		$store = new ApcStore($apc);
		$store->put('foo', 'bar', 1);
	}


	public function testIncrementMethodProperlyCallsAPC()
	{
		$apc = $this->getMock(ApcWrapper::class, array('increment'));
		$apc->expects($this->once())->method('increment')->with($this->equalTo('foo'), $this->equalTo(5));
		$store = new ApcStore($apc);
		$store->increment('foo', 5);
	}


	public function testDecrementMethodProperlyCallsAPC()
	{
		$apc = $this->getMock(ApcWrapper::class, array('decrement'));
		$apc->expects($this->once())->method('decrement')->with($this->equalTo('foo'), $this->equalTo(5));
		$store = new ApcStore($apc);
		$store->decrement('foo', 5);
	}


	public function testStoreItemForeverProperlyCallsAPC()
	{
		$apc = $this->getMock(ApcWrapper::class, array('put'));
		$apc->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(0));
		$store = new ApcStore($apc);
		$store->forever('foo', 'bar');
	}


	public function testForgetMethodProperlyCallsAPC()
	{
		$apc = $this->getMock(ApcWrapper::class, array('delete'));
		$apc->expects($this->once())->method('delete')->with($this->equalTo('foo'));
		$store = new ApcStore($apc);
		$store->forget('foo');
	}

}
