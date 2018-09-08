<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;

/**
 * @group integration
 */
class RedisCacheLockTest extends TestCase
{
    use InteractsWithRedis;

    public function setUp()
    {
        parent::setUp();

        $this->setUpRedis();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->tearDownRedis();
    }

    public function test_redis_locks_can_be_acquired_and_released()
    {
        Cache::store('redis')->lock('foo')->release();
        $this->assertTrue(Cache::store('redis')->lock('foo', 10)->get());
        $this->assertFalse(Cache::store('redis')->lock('foo', 10)->get());
        Cache::store('redis')->lock('foo')->release();
        $this->assertTrue(Cache::store('redis')->lock('foo', 10)->get());
        $this->assertFalse(Cache::store('redis')->lock('foo', 10)->get());
        Cache::store('redis')->lock('foo')->release();
    }

    public function test_redis_locks_can_block_for_seconds()
    {
        Carbon::setTestNow();

        Cache::store('redis')->lock('foo')->release();
        $this->assertEquals('taylor', Cache::store('redis')->lock('foo', 10)->block(1, function () {
            return 'taylor';
        }));

        Cache::store('redis')->lock('foo')->release();
        $this->assertTrue(Cache::store('redis')->lock('foo', 10)->block(1));
    }
}
