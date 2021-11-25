<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Redis\Limiters\Bulk;
use PHPUnit\Framework\TestCase;

class BulkTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownRedis();
    }

    public function testItCacheItemWhenCacheIsNotFull()
    {
        $store = [];

        (new Bulk($this->redis(), 'key', '1', 3, false))
            ->then(function ($items) use (&$store) {
                $store = $items;
            });

        (new Bulk($this->redis(), 'key', '2', 3, false))
            ->then(function ($items) use (&$store) {
                $store = $items;
            });

        $this->assertEquals(0, count($store));

        (new Bulk($this->redis(), 'key', '3', 3, false))
            ->then(function ($items) use (&$store) {
                $store = $items;
            });

        $this->assertEquals(3, count($store));

        $this->assertSame(['1', '2', '3'], $store);

        $store = [];

        (new Bulk($this->redis(), 'key', 2, 2, false))
            ->then(function ($items) use (&$store) {
                $store = $items;
            });

        $this->assertEquals(0, count($store));
    }

    public function testItReleaseCacheItemWhenForceRelease()
    {
        $store = [];

        (new Bulk($this->redis(), 'key', 2, 2, true))
            ->then(function ($items) use (&$store) {
                $store = $items;
            });

        $this->assertEquals(1, count($store));
    }

    public function testItGetItemWhenCacheIsEmpty()
    {
        $store = [];

        (new Bulk($this->redis(), 'key', 1, 0, true))
            ->then(function ($items) use (&$store) {
                $store = $items;
            });

        $this->assertEquals(1, count($store));
    }

    public function testItGetEmptyDataWhenCacheIsEmptyAndPassNullForItem()
    {
        $store = [];

        (new Bulk($this->redis(), 'key', null, 0, true))
            ->then(function ($items) use (&$store) {
                $store = $items;
            });

        $this->assertEquals(0, count($store));
    }

    private function redis()
    {
        return $this->redis['phpredis']->connection();
    }
}
