<?php

namespace Illuminate\Tests\Integration\Cache;

use Memcached;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;

/**
 * @group integration
 */
class CacheLockTest extends TestCase
{
    use InteractsWithRedis;

    public function setup()
    {
        parent::setUp();

        if (! class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached module not installed');
        }
    }

    public function test_memcached_locks_can_be_acquired_and_released()
    {
        Cache::store('memcached')->lock('foo')->release();
        $this->assertTrue(Cache::store('memcached')->lock('foo', 10)->get());
        $this->assertFalse(Cache::store('memcached')->lock('foo', 10)->get());
        Cache::store('memcached')->lock('foo')->release();
        $this->assertTrue(Cache::store('memcached')->lock('foo', 10)->get());
        $this->assertFalse(Cache::store('memcached')->lock('foo', 10)->get());
        Cache::store('memcached')->lock('foo')->release();
    }

    public function test_redis_locks_can_be_acquired_and_released()
    {
        $this->ifRedisAvailable(function () {
            Cache::store('redis')->lock('foo')->release();
            $this->assertTrue(Cache::store('redis')->lock('foo', 10)->get());
            $this->assertFalse(Cache::store('redis')->lock('foo', 10)->get());
            Cache::store('redis')->lock('foo')->release();
            $this->assertTrue(Cache::store('redis')->lock('foo', 10)->get());
            $this->assertFalse(Cache::store('redis')->lock('foo', 10)->get());
            Cache::store('redis')->lock('foo')->release();
        });
    }

    public function test_memcached_locks_can_block_for_seconds()
    {
        Carbon::setTestNow();

        Cache::store('memcached')->lock('foo')->release();
        $this->assertEquals('taylor', Cache::store('memcached')->lock('foo', 10)->block(1, function () {
            return 'taylor';
        }));

        Cache::store('memcached')->lock('foo')->release();
        $this->assertTrue(Cache::store('memcached')->lock('foo', 10)->block(1));
    }

    public function test_redis_locks_can_block_for_seconds()
    {
        $this->ifRedisAvailable(function () {
            Carbon::setTestNow();

            Cache::store('redis')->lock('foo')->release();
            $this->assertEquals('taylor', Cache::store('redis')->lock('foo', 10)->block(1, function () {
                return 'taylor';
            }));

            Cache::store('redis')->lock('foo')->release();
            $this->assertTrue(Cache::store('redis')->lock('foo', 10)->block(1));
        });
    }

    public function test_locks_can_run_callbacks()
    {
        Cache::store('memcached')->lock('foo')->release();
        $this->assertEquals('taylor', Cache::store('memcached')->lock('foo', 10)->get(function () {
            return 'taylor';
        }));
    }

    /**
     * @expectedException \Illuminate\Contracts\Cache\LockTimeoutException
     * @expectedExceptionMessage
     */
    public function test_locks_throw_timeout_if_block_expires()
    {
        Carbon::setTestNow();

        Cache::store('memcached')->lock('foo')->release();
        Cache::store('memcached')->lock('foo', 5)->get();
        $this->assertEquals('taylor', Cache::store('memcached')->lock('foo', 10)->block(1, function () {
            return 'taylor';
        }));
    }
}
