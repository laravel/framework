<?php

class CacheMemcachedStoreTest extends PHPUnit_Framework_TestCase
{
    public function testGetReturnsNullWhenNotFound()
    {
        $memcache = $this->createMock('StdClass', ['get', 'getResultCode']);
        $memcache->expects($this->once())->method('get')->with($this->equalTo('foo:bar'))->will($this->returnValue(null));
        $memcache->expects($this->once())->method('getResultCode')->will($this->returnValue(1));
        $store = new Illuminate\Cache\MemcachedStore($memcache, 'foo');
        $this->assertNull($store->get('bar'));
    }

    public function testMemcacheValueIsReturned()
    {
        $memcache = $this->createMock('StdClass', ['get', 'getResultCode']);
        $memcache->expects($this->once())->method('get')->will($this->returnValue('bar'));
        $memcache->expects($this->once())->method('getResultCode')->will($this->returnValue(0));
        $store = new Illuminate\Cache\MemcachedStore($memcache);
        $this->assertEquals('bar', $store->get('foo'));
    }

    public function testSetMethodProperlyCallsMemcache()
    {
        $memcache = $this->createMock('Memcached', ['set']);
        $memcache->expects($this->once())->method('set')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(60));
        $store = new Illuminate\Cache\MemcachedStore($memcache);
        $store->put('foo', 'bar', 1);
    }

    public function testIncrementMethodProperlyCallsMemcache()
    {
        $memcache = $this->createMock('Memcached', ['increment']);
        $memcache->expects($this->once())->method('increment')->with($this->equalTo('foo'), $this->equalTo(5));
        $store = new Illuminate\Cache\MemcachedStore($memcache);
        $store->increment('foo', 5);
    }

    public function testDecrementMethodProperlyCallsMemcache()
    {
        $memcache = $this->createMock('Memcached', ['decrement']);
        $memcache->expects($this->once())->method('decrement')->with($this->equalTo('foo'), $this->equalTo(5));
        $store = new Illuminate\Cache\MemcachedStore($memcache);
        $store->decrement('foo', 5);
    }

    public function testStoreItemForeverProperlyCallsMemcached()
    {
        $memcache = $this->createMock('Memcached', ['set']);
        $memcache->expects($this->once())->method('set')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(0));
        $store = new Illuminate\Cache\MemcachedStore($memcache);
        $store->forever('foo', 'bar');
    }

    public function testForgetMethodProperlyCallsMemcache()
    {
        $memcache = $this->createMock('Memcached', ['delete']);
        $memcache->expects($this->once())->method('delete')->with($this->equalTo('foo'));
        $store = new Illuminate\Cache\MemcachedStore($memcache);
        $store->forget('foo');
    }

    public function testGetAndSetPrefix()
    {
        $store = new Illuminate\Cache\MemcachedStore(new Memcached(), 'bar');
        $this->assertEquals('bar:', $store->getPrefix());
        $store->setPrefix('foo');
        $this->assertEquals('foo:', $store->getPrefix());
        $store->setPrefix(null);
        $this->assertEmpty($store->getPrefix());
    }
}
