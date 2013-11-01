<?php

use Illuminate\Cache\ArrayStore;

class CacheTaggedCacheTest extends PHPUnit_Framework_TestCase {

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
		$tags = array('bop', 'zap');
		$store->tags($tags)->put('foo', 'bar', 10);
		$this->assertEquals('bar', $store->tags($tags)->get('foo'));
	}


	public function testCacheSavedWithMultipleTagsCanBeFlushed()
	{
		$store = new ArrayStore;
		$tags1 = array('bop', 'zap');
		$store->tags($tags1)->put('foo', 'bar', 10);
		$tags2 = array('bam', 'pow');
		$store->tags($tags2)->put('foo', 'bar', 10);
		$store->tag('zap')->flush();
		$this->assertNull($store->tags($tags1)->get('foo'));
		$this->assertEquals('bar', $store->tags($tags2)->get('foo'));
	}

}