<?php

namespace Illuminate\Tests\Cache;

use Memcached;
use PHPUnit\Framework\TestCase;
use Illuminate\Cache\MemcachedStore;

class CacheMemcachedStoreTest extends TestCase
{
    public function testGetReturnsNullWhenNotFound()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $memcache = $this->getMockBuilder('StdClass')->setMethods(['get', 'getResultCode'])->getMock();
        $memcache->expects($this->once())->method('get')->with($this->equalTo('foo:bar'))->will($this->returnValue(null));
        $memcache->expects($this->once())->method('getResultCode')->will($this->returnValue(1));
        $store = new MemcachedStore($memcache, 'foo');
        $this->assertNull($store->get('bar'));
    }

    public function testMemcacheValueIsReturned()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $memcache = $this->getMockBuilder('StdClass')->setMethods(['get', 'getResultCode'])->getMock();
        $memcache->expects($this->once())->method('get')->will($this->returnValue('bar'));
        $memcache->expects($this->once())->method('getResultCode')->will($this->returnValue(0));
        $store = new MemcachedStore($memcache);
        $this->assertEquals('bar', $store->get('foo'));
    }

    public function testMemcacheGetMultiValuesAreReturnedWithCorrectKeys()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $memcache = $this->getMockBuilder('StdClass')->setMethods(['getMulti', 'getResultCode'])->getMock();
        $memcache->expects($this->once())->method('getMulti')->with(
            ['foo:foo', 'foo:bar', 'foo:baz']
        )->will($this->returnValue([
            'fizz', 'buzz', 'norf',
        ]));
        $memcache->expects($this->once())->method('getResultCode')->will($this->returnValue(0));
        $store = new MemcachedStore($memcache, 'foo');
        $this->assertEquals([
            'foo'   => 'fizz',
            'bar'   => 'buzz',
            'baz'   => 'norf',
        ], $store->many([
            'foo', 'bar', 'baz',
        ]));
    }

    public function testSetMethodProperlyCallsMemcache()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        \Carbon\Carbon::setTestNow($now = \Carbon\Carbon::now());
        $memcache = $this->getMockBuilder('Memcached')->setMethods(['set'])->getMock();
        $memcache->expects($this->once())->method('set')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo($now->timestamp + 60));
        $store = new MemcachedStore($memcache);
        $store->put('foo', 'bar', 1);
        \Carbon\Carbon::setTestNow();
    }

    public function testIncrementMethodProperlyCallsMemcache()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $memcache = $this->getMockBuilder('Memcached')->setMethods(['increment'])->getMock();
        $memcache->expects($this->once())->method('increment')->with($this->equalTo('foo'), $this->equalTo(5));
        $store = new MemcachedStore($memcache);
        $store->increment('foo', 5);
    }

    public function testDecrementMethodProperlyCallsMemcache()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $memcache = $this->getMockBuilder('Memcached')->setMethods(['decrement'])->getMock();
        $memcache->expects($this->once())->method('decrement')->with($this->equalTo('foo'), $this->equalTo(5));
        $store = new MemcachedStore($memcache);
        $store->decrement('foo', 5);
    }

    public function testStoreItemForeverProperlyCallsMemcached()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $memcache = $this->getMockBuilder('Memcached')->setMethods(['set'])->getMock();
        $memcache->expects($this->once())->method('set')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(0));
        $store = new MemcachedStore($memcache);
        $store->forever('foo', 'bar');
    }

    public function testForgetMethodProperlyCallsMemcache()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $memcache = $this->getMockBuilder('Memcached')->setMethods(['delete'])->getMock();
        $memcache->expects($this->once())->method('delete')->with($this->equalTo('foo'));
        $store = new MemcachedStore($memcache);
        $store->forget('foo');
    }

    public function testFlushesCached()
    {
        if (! class_exists('Memcached')) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $memcache = $this->getMockBuilder('Memcached')->setMethods(['flush'])->getMock();
        $memcache->expects($this->once())->method('flush')->willReturn(true);
        $store = new \Illuminate\Cache\MemcachedStore($memcache);
        $result = $store->flush();
        $this->assertTrue($result);
    }

    public function testGetAndSetPrefix()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $store = new MemcachedStore(new Memcached(), 'bar');
        $this->assertEquals('bar:', $store->getPrefix());
        $store->setPrefix('foo');
        $this->assertEquals('foo:', $store->getPrefix());
        $store->setPrefix(null);
        $this->assertEmpty($store->getPrefix());
    }
}
