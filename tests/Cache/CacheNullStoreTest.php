<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\NullStore;
use PHPUnit\Framework\TestCase;

class CacheNullStoreTest extends TestCase
{
    public function testItemsCanNotBeCached()
    {
        $store = new NullStore;
        $store->put('foo', 'bar', 10);
        $this->assertNull($store->get('foo'));
    }

    public function testGetMultipleReturnsMultipleNulls()
    {
        $store = new NullStore;

        $this->assertEquals([
            'foo'   => null,
            'bar'   => null,
        ], $store->many([
            'foo',
            'bar',
        ]));
    }
}
