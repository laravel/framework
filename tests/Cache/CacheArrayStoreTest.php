<?php

use Illuminate\Cache\ArrayStore;

class CacheArrayStoreTest extends PHPUnit_Framework_TestCase {

	public function testItemsCanBeSetAndRetrieved()
	{
		$store = new ArrayStore;
		$store->put('foo', 'bar', 10);
		$this->assertEquals('bar', $store->get('foo'));
	}


	public function testStoreItemForeverProperlyCallsMemcached()
	{
		$mock = $this->getMock('Illuminate\Cache\ArrayStore', array('storeItem'));
		$mock->expects($this->once())->method('storeItem')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(0));
		$mock->forever('foo', 'bar');
	}


	public function testItemsCanBeRemoved()
	{
		$store = new ArrayStore;
		$store->put('foo', 'bar', 10);
		$store->forget('foo');
		$this->assertFalse($store->has('foo'));
	}


	public function testItemsCanBeFlushed()
	{
		$store = new ArrayStore;
		$store->put('foo', 'bar', 10);
		$store->put('baz', 'boom', 10);
		$store->flush();
		$this->assertFalse($store->has('foo'));
		$this->assertFalse($store->has('baz'));
	}

}