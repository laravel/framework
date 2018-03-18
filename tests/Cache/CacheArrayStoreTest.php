<?php

namespace Illuminate\Tests\Cache;

use PHPUnit\Framework\TestCase;
use Illuminate\Cache\ArrayStore;

class CacheArrayStoreTest extends TestCase
{
    public function testItemsCanBeSetAndRetrieved(): void
    {
        $store = new ArrayStore;
        $store->put('foo', 'bar', 10);
        $this->assertEquals('bar', $store->get('foo'));
    }

    public function testMultipleItemsCanBeSetAndRetrieved(): void
    {
        $store = new ArrayStore;
        $store->put('foo', 'bar', 10);
        $store->putMany([
            'fizz'  => 'buz',
            'quz'   => 'baz',
        ], 10);
        $this->assertEquals([
            'foo'   => 'bar',
            'fizz'  => 'buz',
            'quz'   => 'baz',
            'norf'  => null,
        ], $store->many(['foo', 'fizz', 'quz', 'norf']));
    }

    public function testStoreItemForeverProperlyStoresInArray(): void
    {
        $mock = $this->getMockBuilder('Illuminate\Cache\ArrayStore')->setMethods(['put'])->getMock();
        $mock->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(0));
        $mock->forever('foo', 'bar');
    }

    public function testValuesCanBeIncremented(): void
    {
        $store = new ArrayStore;
        $store->put('foo', 1, 10);
        $store->increment('foo');
        $this->assertEquals(2, $store->get('foo'));
    }

    public function testNonExistingKeysCanBeIncremented(): void
    {
        $store = new ArrayStore;
        $store->increment('foo');
        $this->assertEquals(1, $store->get('foo'));
    }

    public function testValuesCanBeDecremented(): void
    {
        $store = new ArrayStore;
        $store->put('foo', 1, 10);
        $store->decrement('foo');
        $this->assertEquals(0, $store->get('foo'));
    }

    public function testItemsCanBeRemoved(): void
    {
        $store = new ArrayStore;
        $store->put('foo', 'bar', 10);
        $store->forget('foo');
        $this->assertNull($store->get('foo'));
    }

    public function testItemsCanBeFlushed(): void
    {
        $store = new ArrayStore;
        $store->put('foo', 'bar', 10);
        $store->put('baz', 'boom', 10);
        $result = $store->flush();
        $this->assertTrue($result);
        $this->assertNull($store->get('foo'));
        $this->assertNull($store->get('baz'));
    }

    public function testCacheKey(): void
    {
        $store = new ArrayStore;
        $this->assertEmpty($store->getPrefix());
    }
}
