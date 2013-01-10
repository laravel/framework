<?php

class CacheStoreTest extends PHPUnit_Framework_TestCase {

	public function testGetReturnsInMemoryItems()
	{
		$store = $this->getMockStore();
		$store->setInMemory('foo', 'bar');
		$this->assertEquals('bar', $store->get('foo'));
	}


	public function testGetReturnsValueFromCache()
	{
		$store = $this->getMockStore();
		$store->expects($this->once())->method('retrieveItem')->with($this->equalTo('foo'))->will($this->returnValue('bar'));
		$this->assertEquals('bar', $store->get('foo'));
		$this->assertEquals('bar', $store->getFromMemory('foo'));
	}


	public function testDefaultValueIsReturned()
	{
		$store = $this->getMockStore();
		$this->assertEquals('bar', $store->get('foo', 'bar'));
		$this->assertEquals('baz', $store->get('boom', function() { return 'baz'; }));
	}


	public function testStoreMethodStoresItemInMemory()
	{
		$store = $this->getMockStore();
		$store->put('foo', 'bar', 10);
		$this->assertEquals('bar', $store->getFromMemory('foo'));
	}


	public function testStoreMethodCallsDriver()
	{
		$store = $this->getMockStore();
		$store->expects($this->once())->method('storeItem')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(10));
		$store->put('foo', 'bar', 10);
	}


	public function testForgetRemovesItemFromMemory()
	{
		$store = $this->getMockStore();
		$store->setInMemory('foo', 'bar');
		$store->forget('foo');
		$this->assertFalse($store->existsInMemory('foo'));
	}


	public function testForgetMethodCallsDriver()
	{
		$store = $this->getMockStore();
		$store->expects($this->once())->method('removeItem')->with($this->equalTo('foo'));
		$store->forget('foo');
	}


	public function testSettingDefaultCacheTime()
	{
		$store = $this->getMockStore();
		$store->setDefaultCacheTime(10);
		$this->assertEquals(10, $store->getDefaultCacheTime());
	}


	public function testHasMethod()
	{
		$store = $this->getMockStore();
		$this->assertFalse($store->has('foo'));
		$store->setInMemory('foo', 'bar');
		$this->assertTrue($store->has('foo'));
	}


	public function testArrayAccess()
	{
		$store = $this->getMockStore();
		$store['foo'] = 'bar';
		$this->assertEquals('bar', $store['foo']);
		$this->assertTrue(isset($store['foo']));
		unset($store['foo']);
		$this->assertFalse(isset($store['foo']));
	}


	public function testRememberMethodReturnsItemsThatExist()
	{
		$store = $this->getMockStore();
		$store->setInMemory('foo', 'bar');
		$this->assertEquals('bar', $store->remember('foo', 30, function() { return 'boom'; }));
	}


	public function testRememberMethodCallsPutAndReturnsDefault()
	{
		$store = $this->getMockStore();
		$store->expects($this->once())->method('storeItem')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(10));
		$result = $store->remember('foo', 10, function() { return 'bar'; });
		$this->assertEquals('bar', $result);
	}


	public function testRememberForeverMethodReturnsItemsThatExist()
	{
		$store = $this->getMockStore();
		$store->setInMemory('foo', 'bar');
		$this->assertEquals('bar', $store->rememberForever('foo', function() { return 'boom'; }));
	}


	public function testRememberForeverMethodCallsForeverAndReturnsDefault()
	{
		$store = $this->getMockStore();
		$store->expects($this->once())->method('storeItemForever')->with($this->equalTo('foo'), $this->equalTo('bar'));
		$result = $store->rememberForever('foo', function() { return 'bar'; });
		$this->assertEquals('bar', $result);
	}


	public function testForeverMethodStoresItemInMemory()
	{
		$store = $this->getMockStore();
		$store->forever('foo', 'bar');
		$this->assertEquals('bar', $store->getFromMemory('foo'));
	}


	public function testForeverMethodCallsDriver()
	{
		$store = $this->getMockStore();
		$store->expects($this->once())->method('storeItemForever')->with($this->equalTo('foo'), $this->equalTo('bar'));
		$store->forever('foo', 'bar');
	}


	public function testFlushClearsMemoryItems()
	{
		$store = $this->getMockStore();
		$store->setInMemory('foo', 'bar');
		$store->flush();
		$this->assertEquals(array(), $store->getMemory());
	}


	public function testFlushCallsDriver()
	{
		$store = $this->getMockStore();
		$store->expects($this->once())->method('flushItems');
		$store->flush();
	}


	protected function getMockStore()
	{
		return $this->getMock('Illuminate\Cache\Store', array('retrieveItem', 'storeItem', 'storeItemForever', 'removeItem', 'flushItems'));
	}

}