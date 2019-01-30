<?php

namespace Illuminate\Tests\Cache;

use DateTime;
use stdClass;
use DateInterval;
use Mockery as m;
use Illuminate\Cache\TagSet;
use PHPUnit\Framework\TestCase;
use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Cache\RedisTaggedCache;

class CacheTaggedCacheTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testCacheCanBeSavedWithMultipleTags()
    {
        $store = new ArrayStore;
        $tags = ['bop', 'zap'];
        $store->tags($tags)->put('foo', 'bar', 10);
        $this->assertEquals('bar', $store->tags($tags)->get('foo'));
    }

    public function testCacheCanBeSetWithDatetimeArgument()
    {
        $store = new ArrayStore;
        $tags = ['bop', 'zap'];
        $duration = new DateTime;
        $duration->add(new DateInterval('PT10M'));
        $store->tags($tags)->put('foo', 'bar', $duration);
        $this->assertEquals('bar', $store->tags($tags)->get('foo'));
    }

    public function testCacheSavedWithMultipleTagsCanBeFlushed()
    {
        $store = new ArrayStore;
        $tags1 = ['bop', 'zap'];
        $store->tags($tags1)->put('foo', 'bar', 10);
        $tags2 = ['bam', 'pow'];
        $store->tags($tags2)->put('foo', 'bar', 10);
        $store->tags('zap')->flush();
        $this->assertNull($store->tags($tags1)->get('foo'));
        $this->assertEquals('bar', $store->tags($tags2)->get('foo'));
    }

    public function testTagsWithStringArgument()
    {
        $store = new ArrayStore;
        $store->tags('bop')->put('foo', 'bar', 10);
        $this->assertEquals('bar', $store->tags('bop')->get('foo'));
    }

    public function testTagsWithIncrementCanBeFlushed()
    {
        $store = new ArrayStore;
        $store->tags('bop')->increment('foo', 5);
        $this->assertEquals(5, $store->tags('bop')->get('foo'));
        $store->tags('bop')->flush();
        $this->assertNull($store->tags('bop')->get('foo'));
    }

    public function testTagsWithDecrementCanBeFlushed()
    {
        $store = new ArrayStore;
        $store->tags('bop')->decrement('foo', 5);
        $this->assertEquals(-5, $store->tags('bop')->get('foo'));
        $store->tags('bop')->flush();
        $this->assertNull($store->tags('bop')->get('foo'));
    }

    public function testTagsCacheForever()
    {
        $store = new ArrayStore;
        $tags = ['bop', 'zap'];
        $store->tags($tags)->forever('foo', 'bar');
        $this->assertEquals('bar', $store->tags($tags)->get('foo'));
    }

    public function testRedisCacheTagsPushForeverKeysCorrectly()
    {
        $store = m::mock(Store::class);
        $tagSet = m::mock(TagSet::class, [$store, ['foo', 'bar']]);
        $tagSet->shouldReceive('getNamespace')->andReturn('foo|bar');
        $tagSet->shouldReceive('getNames')->andReturn(['foo', 'bar']);
        $redis = new RedisTaggedCache($store, $tagSet);
        $store->shouldReceive('getPrefix')->andReturn('prefix:');
        $store->shouldReceive('connection')->andReturn($conn = m::mock(stdClass::class));
        $conn->shouldReceive('sadd')->once()->with('prefix:foo:forever_ref', 'prefix:'.sha1('foo|bar').':key1');
        $conn->shouldReceive('sadd')->once()->with('prefix:bar:forever_ref', 'prefix:'.sha1('foo|bar').':key1');

        $store->shouldReceive('forever')->with(sha1('foo|bar').':key1', 'key1:value');

        $redis->forever('key1', 'key1:value');
    }

    public function testRedisCacheTagsPushStandardKeysCorrectly()
    {
        $store = m::mock(Store::class);
        $tagSet = m::mock(TagSet::class, [$store, ['foo', 'bar']]);
        $tagSet->shouldReceive('getNamespace')->andReturn('foo|bar');
        $tagSet->shouldReceive('getNames')->andReturn(['foo', 'bar']);
        $redis = new RedisTaggedCache($store, $tagSet);
        $store->shouldReceive('getPrefix')->andReturn('prefix:');
        $store->shouldReceive('connection')->andReturn($conn = m::mock(stdClass::class));
        $conn->shouldReceive('sadd')->once()->with('prefix:foo:standard_ref', 'prefix:'.sha1('foo|bar').':key1');
        $conn->shouldReceive('sadd')->once()->with('prefix:bar:standard_ref', 'prefix:'.sha1('foo|bar').':key1');
        $store->shouldReceive('push')->with(sha1('foo|bar').':key1', 'key1:value');
        $store->shouldReceive('put')->andReturn(true);

        $redis->put('key1', 'key1:value', 60);
    }

    public function testRedisCacheTagsPushForeverKeysCorrectlyWithNullTTL()
    {
        $store = m::mock(Store::class);
        $tagSet = m::mock(TagSet::class, [$store, ['foo', 'bar']]);
        $tagSet->shouldReceive('getNamespace')->andReturn('foo|bar');
        $tagSet->shouldReceive('getNames')->andReturn(['foo', 'bar']);
        $redis = new RedisTaggedCache($store, $tagSet);
        $store->shouldReceive('getPrefix')->andReturn('prefix:');
        $store->shouldReceive('connection')->andReturn($conn = m::mock(stdClass::class));
        $conn->shouldReceive('sadd')->once()->with('prefix:foo:forever_ref', 'prefix:'.sha1('foo|bar').':key1');
        $conn->shouldReceive('sadd')->once()->with('prefix:bar:forever_ref', 'prefix:'.sha1('foo|bar').':key1');
        $store->shouldReceive('forever')->with(sha1('foo|bar').':key1', 'key1:value');

        $redis->put('key1', 'key1:value');
    }

    public function testRedisCacheTagsCanBeFlushed()
    {
        $store = m::mock(Store::class);
        $tagSet = m::mock(TagSet::class, [$store, ['foo', 'bar']]);
        $tagSet->shouldReceive('getNamespace')->andReturn('foo|bar');
        $redis = new RedisTaggedCache($store, $tagSet);
        $store->shouldReceive('getPrefix')->andReturn('prefix:');
        $store->shouldReceive('connection')->andReturn($conn = m::mock(stdClass::class));

        // Forever tag keys
        $conn->shouldReceive('smembers')->once()->with('prefix:foo:forever_ref')->andReturn(['key1', 'key2']);
        $conn->shouldReceive('smembers')->once()->with('prefix:bar:forever_ref')->andReturn(['key3']);
        $conn->shouldReceive('del')->once()->with('key1', 'key2');
        $conn->shouldReceive('del')->once()->with('key3');
        $conn->shouldReceive('del')->once()->with('prefix:foo:forever_ref');
        $conn->shouldReceive('del')->once()->with('prefix:bar:forever_ref');

        // Standard tag keys
        $conn->shouldReceive('smembers')->once()->with('prefix:foo:standard_ref')->andReturn(['key4', 'key5']);
        $conn->shouldReceive('smembers')->once()->with('prefix:bar:standard_ref')->andReturn(['key6']);
        $conn->shouldReceive('del')->once()->with('key4', 'key5');
        $conn->shouldReceive('del')->once()->with('key6');
        $conn->shouldReceive('del')->once()->with('prefix:foo:standard_ref');
        $conn->shouldReceive('del')->once()->with('prefix:bar:standard_ref');

        $tagSet->shouldReceive('reset')->once();

        $redis->flush();
    }
}
