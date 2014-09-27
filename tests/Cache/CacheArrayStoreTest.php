<?php

use Illuminate\Cache\ArrayStore;

class CacheArrayStoreTest extends PHPUnit_Framework_TestCase {

	public function testItemsCanBeSetAndRetrieved()
	{
		$store = new ArrayStore;
		$store->put('foo', 'bar', 10);
		$this->assertEquals('bar', $store->get('foo'));
	}


	public function testStoreItemForeverProperlyStoresInArray()
	{
		$mock = $this->getMock('Illuminate\Cache\ArrayStore', ['put']);
		$mock->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(0));
		$mock->forever('foo', 'bar');
	}


	public function testValuesCanBeIncremented()
	{
		$store = new ArrayStore;
		$store->put('foo', 1, 10);
		$store->increment('foo');
		$this->assertEquals(2, $store->get('foo'));
	}


	public function testValuesCanBeDecremented()
	{
		$store = new ArrayStore;
		$store->put('foo', 1, 10);
		$store->decrement('foo');
		$this->assertEquals(0, $store->get('foo'));
	}


	public function testItemsCanBeRemoved()
	{
		$store = new ArrayStore;
		$store->put('foo', 'bar', 10);
		$store->forget('foo');
		$this->assertNull($store->get('foo'));
	}


	public function testItemsCanBeFlushed()
	{
		$store = new ArrayStore;
		$store->put('foo', 'bar', 10);
		$store->put('baz', 'boom', 10);
		$store->flush();
		$this->assertNull($store->get('foo'));
		$this->assertNull($store->get('baz'));
	}


	public function testCacheKey()
	{
		$store = new ArrayStore;
		$this->assertEquals('', $store->getPrefix());
	}

}
