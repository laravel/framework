<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\MemcachedStore;
use Illuminate\Support\Carbon;
use Memcached;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class CacheMemcachedStoreTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testGetReturnsNullWhenNotFound()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $memcache = $this->getMockBuilder(stdClass::class)->addMethods(['get', 'getResultCode'])->getMock();
        $memcache->expects($this->once())->method('get')->with($this->equalTo('foo:bar'))->willReturn(null);
        $memcache->expects($this->once())->method('getResultCode')->willReturn(1);
        $store = new MemcachedStore($memcache, 'foo');
        $this->assertNull($store->get('bar'));
    }

    public function testMemcacheValueIsReturned()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $memcache = $this->getMockBuilder(stdClass::class)->addMethods(['get', 'getResultCode'])->getMock();
        $memcache->expects($this->once())->method('get')->willReturn('bar');
        $memcache->expects($this->once())->method('getResultCode')->willReturn(0);
        $store = new MemcachedStore($memcache);
        $this->assertSame('bar', $store->get('foo'));
    }

    public function testMemcacheGetMultiValuesAreReturnedWithCorrectKeys()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $memcache = $this->getMockBuilder(stdClass::class)->addMethods(['getMulti', 'getResultCode'])->getMock();
        $memcache->expects($this->once())->method('getMulti')->with(
            ['foo:foo', 'foo:bar', 'foo:baz']
        )->willReturn([
            'fizz', 'buzz', 'norf',
        ]);
        $memcache->expects($this->once())->method('getResultCode')->willReturn(0);
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

        Carbon::setTestNow($now = Carbon::now());
        $memcache = $this->getMockBuilder(Memcached::class)->onlyMethods(['set'])->getMock();
        $memcache->expects($this->once())->method('set')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo($now->timestamp + 60))->willReturn(true);
        $store = new MemcachedStore($memcache);
        $result = $store->put('foo', 'bar', 60);
        $this->assertTrue($result);
        Carbon::setTestNow();
    }

    public function testIncrementMethodProperlyCallsMemcache()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        /* @link https://github.com/php-memcached-dev/php-memcached/pull/468 */
        if (version_compare(phpversion(), '8.0.0', '>=')) {
            $this->markTestSkipped('Test broken due to parse error in PHP Memcached.');
        }

        $memcached = m::mock(Memcached::class);
        $memcached->shouldReceive('increment')->with('foo', 5)->once()->andReturn(5);

        $store = new MemcachedStore($memcached);
        $store->increment('foo', 5);
    }

    public function testDecrementMethodProperlyCallsMemcache()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        /* @link https://github.com/php-memcached-dev/php-memcached/pull/468 */
        if (version_compare(phpversion(), '8.0.0', '>=')) {
            $this->markTestSkipped('Test broken due to parse error in PHP Memcached.');
        }

        $memcached = m::mock(Memcached::class);
        $memcached->shouldReceive('decrement')->with('foo', 5)->once()->andReturn(0);

        $store = new MemcachedStore($memcached);
        $store->decrement('foo', 5);
    }

    public function testStoreItemForeverProperlyCallsMemcached()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $memcache = $this->getMockBuilder(Memcached::class)->onlyMethods(['set'])->getMock();
        $memcache->expects($this->once())->method('set')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(0))->willReturn(true);
        $store = new MemcachedStore($memcache);
        $result = $store->forever('foo', 'bar');
        $this->assertTrue($result);
    }

    public function testForgetMethodProperlyCallsMemcache()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $memcache = $this->getMockBuilder(Memcached::class)->onlyMethods(['delete'])->getMock();
        $memcache->expects($this->once())->method('delete')->with($this->equalTo('foo'));
        $store = new MemcachedStore($memcache);
        $store->forget('foo');
    }

    public function testFlushesCached()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $memcache = $this->getMockBuilder(Memcached::class)->onlyMethods(['flush'])->getMock();
        $memcache->expects($this->once())->method('flush')->willReturn(true);
        $store = new MemcachedStore($memcache);
        $result = $store->flush();
        $this->assertTrue($result);
    }

    public function testGetAndSetPrefix()
    {
        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $store = new MemcachedStore(new Memcached, 'bar');
        $this->assertSame('bar:', $store->getPrefix());
        $store->setPrefix('foo');
        $this->assertSame('foo:', $store->getPrefix());
        $store->setPrefix(null);
        $this->assertEmpty($store->getPrefix());
    }
}
