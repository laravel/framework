<?php

use Mockery as m;
use Illuminate\Cache\ArrayStore;

class CacheTaggedCacheTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testSectionCanBeFlushed()
	{
		$store = new ArrayStore;
		$store->section('bop')->put('foo', 'bar', 10);
		$store->section('zap')->put('baz', 'boom', 10);
		$store->section('bop')->flush();
		$this->assertNull($store->section('bop')->get('foo'));
		$this->assertEquals('boom', $store->section('zap')->get('baz'));
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
		$duration = new DateTime();
		$duration->add(new DateInterval("PT10M"));
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


	public function testTagsCacheForever()
	{
		$store = new ArrayStore;
		$tags = ['bop', 'zap'];
		$store->tags($tags)->forever('foo', 'bar');
		$this->assertEquals('bar', $store->tags($tags)->get('foo'));
	}


	public function testRedisCacheTagsPushForeverKeysCorrectly()
	{
		$store = m::mock('Illuminate\Cache\StoreInterface');
		$tagSet = m::mock('Illuminate\Cache\TagSet', [$store, ['foo', 'bar']]);
		$tagSet->shouldReceive('getNamespace')->andReturn('foo|bar');
		$redis = new Illuminate\Cache\RedisTaggedCache($store, $tagSet);
		$store->shouldReceive('getPrefix')->andReturn('prefix:');
		$store->shouldReceive('connection')->andReturn($conn = m::mock('StdClass'));
		$conn->shouldReceive('lpush')->once()->with('prefix:foo:forever', 'prefix:'.sha1('foo|bar').':key1');
		$conn->shouldReceive('lpush')->once()->with('prefix:bar:forever', 'prefix:'.sha1('foo|bar').':key1');
		$store->shouldReceive('forever')->with(sha1('foo|bar').':key1', 'key1:value');

		$redis->forever('key1', 'key1:value');
	}


	public function testRedisCacheForeverTagsCanBeFlushed()
	{
		$store = m::mock('Illuminate\Cache\StoreInterface');
		$tagSet = m::mock('Illuminate\Cache\TagSet', [$store, ['foo', 'bar']]);
		$tagSet->shouldReceive('getNamespace')->andReturn('foo|bar');
		$redis = new Illuminate\Cache\RedisTaggedCache($store, $tagSet);
		$store->shouldReceive('getPrefix')->andReturn('prefix:');
		$store->shouldReceive('connection')->andReturn($conn = m::mock('StdClass'));
		$conn->shouldReceive('lrange')->once()->with('prefix:foo:forever', 0, -1)->andReturn(['key1', 'key2']);
		$conn->shouldReceive('lrange')->once()->with('prefix:bar:forever', 0, -1)->andReturn(['key3']);
		$conn->shouldReceive('del')->once()->with('key1', 'key2');
		$conn->shouldReceive('del')->once()->with('key3');
		$conn->shouldReceive('del')->once()->with('prefix:foo:forever');
		$conn->shouldReceive('del')->once()->with('prefix:bar:forever');
		$tagSet->shouldReceive('reset')->once();

		$redis->flush();
	}

}
