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
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testGetReturnsNullWhenNotFound()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->onlyMethods(['get', 'getResultCode'])->getMock();
        $memcache->expects($this->once())->method('get')->with($this->equalTo('foo:bar'))->willReturn(null);
        $memcache->expects($this->once())->method('getResultCode')->willReturn(1);
        $store = new MemcachedStore($memcache, 'foo:');
        $this->assertNull($store->get('bar'));
    }

    public function testMemcacheValueIsReturned()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->onlyMethods(['get', 'getResultCode'])->getMock();
        $memcache->expects($this->once())->method('get')->willReturn('bar');
        $memcache->expects($this->once())->method('getResultCode')->willReturn(0);
        $store = new MemcachedStore($memcache);
        $this->assertSame('bar', $store->get('foo'));
    }

    public function testMemcacheGetMultiValuesAreReturnedWithCorrectKeys()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->onlyMethods(['getMulti', 'getResultCode'])->getMock();
        $memcache->expects($this->once())->method('getMulti')->with(
            ['foo:foo', 'foo:bar', 'foo:baz']
        )->willReturn([
            'fizz', 'buzz', 'norf',
        ]);
        $memcache->expects($this->once())->method('getResultCode')->willReturn(0);
        $store = new MemcachedStore($memcache, 'foo:');
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
        $memcache = $this->getMockBuilder(Memcached::class)->onlyMethods(['set'])->getMock();
        $invocations = [];
        $memcache->method('set')->willReturnCallback(function ($key, $value, $expiry) use (&$invocations) {
            $invocations[] = func_get_args();

            return true;
        });

        $store = new MemcachedStore($memcache);
        $result = $store->put('foo', 'bar', 60);

        $this->assertTrue($result);
        $this->assertCount(2, $invocations);

        $this->assertSame('foo', $invocations[0][0]);
        $this->assertSame('bar', $invocations[0][1]);
        $this->assertSame($now->timestamp + 60, $invocations[0][2]);

        $this->assertSame('foo_ttl', $invocations[1][0]);
        $this->assertSame($now->timestamp + 60, $invocations[1][1]);
        $this->assertSame($now->timestamp + 60, $invocations[1][2]);

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
        $memcache = $this->getMockBuilder(Memcached::class)->onlyMethods(['set'])->getMock();
        $invocations = [];
        $memcache->method('set')->willReturnCallback(function ($key, $value, $expiry) use (&$invocations) {
            $invocations[] = func_get_args();

            return true;
        });
        $store = new MemcachedStore($memcache);
        $result = $store->forever('foo', 'bar');
        $this->assertTrue($result);
        $this->assertCount(2, $invocations);

        $this->assertSame('foo', $invocations[0][0]);
        $this->assertSame('bar', $invocations[0][1]);
        $this->assertSame(0, $invocations[0][2]);

        $this->assertSame('foo_ttl', $invocations[1][0]);
        $this->assertSame(0, $invocations[1][1]);
        $this->assertSame(0, $invocations[1][2]);

        Carbon::setTestNow(null);
    }

    public function testForgetMethodProperlyCallsMemcache()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->onlyMethods(['delete'])->getMock();
        $invocations = [];
        $memcache->method('delete')->willReturnCallback(function ($key) use (&$invocations) {
            $invocations[] = func_get_args();

            return true;
        });
        $store = new MemcachedStore($memcache);
        $store->forget('foo');
        $this->assertCount(2, $invocations);

        $this->assertSame('foo', $invocations[0][0]);
        $this->assertSame('foo_ttl', $invocations[1][0]);
    }

    public function testFlushesCached()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->onlyMethods(['flush'])->getMock();
        $memcache->expects($this->once())->method('flush')->willReturn(true);
        $store = new MemcachedStore($memcache);
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
