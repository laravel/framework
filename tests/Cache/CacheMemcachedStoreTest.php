<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\MemcachedStore;
use Illuminate\Support\Carbon;
use Memcached;
use Mockery as m;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('memcached')]
class CacheMemcachedStoreTest extends TestCase
{
    public function testGetReturnsNullWhenNotFound()
    {
        $memcached = $this->getMockBuilder(Memcached::class)->onlyMethods(['get', 'getResultCode'])->getMock();
        $memcached->expects($this->once())->method('get')->with($this->equalTo('foo:bar'))->willReturn(null);
        $memcached->expects($this->once())->method('getResultCode')->willReturn(1);
        $store = new MemcachedStore($memcached, 'foo:');
        $this->assertNull($store->get('bar'));
    }

    public function testMemcacheValueIsReturned()
    {
        $memcached = $this->getMockBuilder(Memcached::class)->onlyMethods(['get', 'getResultCode'])->getMock();
        $memcached->expects($this->once())->method('get')->willReturn('bar');
        $memcached->expects($this->once())->method('getResultCode')->willReturn(0);
        $store = new MemcachedStore($memcached);
        $this->assertSame('bar', $store->get('foo'));
    }

    public function testMemcacheGetMultiValuesAreReturnedWithCorrectKeys()
    {
        $memcached = $this->getMockBuilder(Memcached::class)->onlyMethods(['getMulti', 'getResultCode'])->getMock();
        $memcached->expects($this->once())->method('getMulti')->with(
            ['foo:foo', 'foo:bar', 'foo:baz']
        )->willReturn([
            'fizz', 'buzz', 'norf',
        ]);
        $memcached->expects($this->once())->method('getResultCode')->willReturn(0);
        $store = new MemcachedStore($memcached, 'foo:');
        $this->assertEquals([
            'foo' => 'fizz',
            'bar' => 'buzz',
            'baz' => 'norf',
        ], $store->many([
            'foo', 'bar', 'baz',
        ]));
    }

    public function testSetMethodProperlyCallsMemcache()
    {
        Carbon::setTestNow($now = Carbon::now());
        $memcached = $this->getMockBuilder(Memcached::class)->onlyMethods(['set'])->getMock();
        $memcached->expects($this->once())->method('set')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo($now->timestamp + 60))->willReturn(true);
        $store = new MemcachedStore($memcached);
        $result = $store->put('foo', 'bar', 60);
        $this->assertTrue($result);
        Carbon::setTestNow(null);
    }

    public function testIncrementMethodProperlyCallsMemcache()
    {
        $memcached = m::mock(Memcached::class);
        $memcached->shouldReceive('increment')->with('foo', 5)->once()->andReturn(5);

        $store = new MemcachedStore($memcached);
        $store->increment('foo', 5);
    }

    public function testDecrementMethodProperlyCallsMemcache()
    {
        $memcached = m::mock(Memcached::class);
        $memcached->shouldReceive('decrement')->with('foo', 5)->once()->andReturn(0);

        $store = new MemcachedStore($memcached);
        $store->decrement('foo', 5);
    }

    public function testStoreItemForeverProperlyCallsMemcached()
    {
        $memcached = $this->getMockBuilder(Memcached::class)->onlyMethods(['set'])->getMock();
        $memcached->expects($this->once())->method('set')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(0))->willReturn(true);
        $store = new MemcachedStore($memcached);
        $result = $store->forever('foo', 'bar');
        $this->assertTrue($result);
    }

    public function testForgetMethodProperlyCallsMemcache()
    {
        $memcached = $this->getMockBuilder(Memcached::class)->onlyMethods(['delete'])->getMock();
        $memcached->expects($this->once())->method('delete')->with($this->equalTo('foo'));
        $store = new MemcachedStore($memcached);
        $store->forget('foo');
    }

    public function testFlushesCached()
    {
        $memcached = $this->getMockBuilder(Memcached::class)->onlyMethods(['flush'])->getMock();
        $memcached->expects($this->once())->method('flush')->willReturn(true);
        $store = new MemcachedStore($memcached);
        $result = $store->flush();
        $this->assertTrue($result);
    }

    public function testGetAndSetPrefix()
    {
        $store = new MemcachedStore(new Memcached, 'bar');
        $this->assertSame('bar', $store->getPrefix());
        $store->setPrefix('foo');
        $this->assertSame('foo', $store->getPrefix());
        $store->setPrefix(null);
        $this->assertEmpty($store->getPrefix());
    }
}
