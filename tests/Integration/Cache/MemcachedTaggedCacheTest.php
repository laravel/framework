<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Support\Facades\Cache;

/**
 * @group integration
 */
class MemcachedTaggedCacheTest extends MemcachedIntegrationTest
{
    public function test_memcached_can_store_and_retrieve_tagged_cache_items()
    {
        $store = Cache::store('memcached');

        $store->tags(['people', 'artists'])->put('John', 'foo', 1);
        $store->tags(['people', 'authors'])->put('Anne', 'bar', 1);

        $this->assertEquals('foo', $store->tags(['people', 'artists'])->get('John'));
        $this->assertEquals('bar', $store->tags(['people', 'authors'])->get('Anne'));

        $store->tags(['people', 'artists'])->put('John', 'baz');
        $store->tags(['people', 'authors'])->put('Anne', 'qux');

        $this->assertEquals('baz', $store->tags(['people', 'artists'])->get('John'));
        $this->assertEquals('qux', $store->tags(['people', 'authors'])->get('Anne'));

        $store->tags('authors')->flush();
        $this->assertNull($store->tags(['people', 'authors'])->get('Anne'));

        $store->tags(['people', 'authors'])->flush();
        $this->assertNull($store->tags(['people', 'artists'])->get('John'));
    }

    public function test_memcached_can_store_many_tagged_cache_items()
    {
        $store = Cache::store('memcached');

        $store->tags(['people', 'artists'])->putMany(['John' => 'foo', 'Jane' => 'bar'], 1);

        $this->assertEquals('foo', $store->tags(['people', 'artists'])->get('John'));
        $this->assertEquals('bar', $store->tags(['people', 'artists'])->get('Jane'));

        $store->tags(['people', 'artists'])->putMany(['John' => 'baz', 'Jane' => 'qux']);

        $this->assertEquals('baz', $store->tags(['people', 'artists'])->get('John'));
        $this->assertEquals('qux', $store->tags(['people', 'artists'])->get('Jane'));

        $store->tags(['people', 'artists'])->putMany(['John' => 'baz', 'Jane' => 'qux'], -1);

        $this->assertNull($store->tags(['people', 'artists'])->get('John'));
        $this->assertNull($store->tags(['people', 'artists'])->get('Jane'));
    }
}
