<?php

use Carbon\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Cache;

/**
 * @group integration
 */
class CacheLockTest extends TestCase
{
    public function test_locks_can_be_acquired_and_released()
    {
        // Memcached...
        Cache::store('memcached')->lock('foo')->release();
        $this->assertTrue(Cache::store('memcached')->lock('foo', 10)->get());
        $this->assertFalse(Cache::store('memcached')->lock('foo', 10)->get());
        Cache::store('memcached')->lock('foo')->release();
        $this->assertTrue(Cache::store('memcached')->lock('foo', 10)->get());
        $this->assertFalse(Cache::store('memcached')->lock('foo', 10)->get());
        Cache::store('memcached')->lock('foo')->release();

        // Redis...
        Cache::store('redis')->lock('foo')->release();
        $this->assertTrue(Cache::store('redis')->lock('foo', 10)->get());
        $this->assertFalse(Cache::store('redis')->lock('foo', 10)->get());
        Cache::store('redis')->lock('foo')->release();
        $this->assertTrue(Cache::store('redis')->lock('foo', 10)->get());
        $this->assertFalse(Cache::store('redis')->lock('foo', 10)->get());
        Cache::store('redis')->lock('foo')->release();
    }

    public function test_locks_can_run_callbacks()
    {
        Cache::store('memcached')->lock('foo')->release();
        $this->assertEquals('taylor', Cache::store('memcached')->lock('foo', 10)->get(function () {
            return 'taylor';
        }));
    }

    public function test_locks_can_block()
    {
        Cache::store('memcached')->lock('foo')->release();
        Cache::store('memcached')->lock('foo', 1)->get();
        $this->assertEquals('taylor', Cache::store('memcached')->lock('foo', 10)->block(function () {
            return 'taylor';
        }));

        Cache::store('memcached')->lock('foo')->release();
        Cache::store('memcached')->lock('foo', 1)->get();
        $this->assertTrue(Cache::store('memcached')->lock('foo', 10)->block());

        Cache::store('redis')->lock('foo')->release();
        Cache::store('redis')->lock('foo', 1)->get();
        $this->assertEquals('taylor', Cache::store('redis')->lock('foo', 10)->block(function () {
            return 'taylor';
        }));

        Cache::store('redis')->lock('foo')->release();
        Cache::store('redis')->lock('foo', 1)->get();
        $this->assertTrue(Cache::store('redis')->lock('foo', 10)->block());
    }

    public function test_locks_can_block_for_seconds()
    {
        Carbon::setTestNow();

        Cache::store('memcached')->lock('foo')->release();
        $this->assertEquals('taylor', Cache::store('memcached')->lock('foo', 10)->blockFor(1, function () {
            return 'taylor';
        }));

        Cache::store('memcached')->lock('foo')->release();
        $this->assertTrue(Cache::store('memcached')->lock('foo', 10)->blockFor(1));

        Cache::store('redis')->lock('foo')->release();
        $this->assertEquals('taylor', Cache::store('redis')->lock('foo', 10)->blockFor(1, function () {
            return 'taylor';
        }));

        Cache::store('redis')->lock('foo')->release();
        $this->assertTrue(Cache::store('redis')->lock('foo', 10)->blockFor(1));
    }

    /**
     * @expectedException \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function test_locks_throw_timeout_if_block_expires()
    {
        Carbon::setTestNow();

        Cache::store('memcached')->lock('foo')->release();
        Cache::store('memcached')->lock('foo', 5)->get();
        $this->assertEquals('taylor', Cache::store('memcached')->lock('foo', 10)->blockFor(1, function () {
            return 'taylor';
        }));
    }
}
