<?php

namespace Illuminate\Tests\Integration\Cache;

use Memcached;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Cache;

/**
 * @group integration
 */
class MemcachedCacheLockTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        if (! extension_loaded('memcached')) {
            $this->markTestSkipped('Memcached module not installed');
        }

        // Determine whether there is a running Memcached instance
        $testConnection = new Memcached;
        $testConnection->addServer(
            env('MEMCACHED_HOST', '127.0.0.1'),
            env('MEMCACHED_PORT', 11211)
        );
        $testConnection->getVersion();

        if ($testConnection->getResultCode() > Memcached::RES_SUCCESS) {
            $this->markTestSkipped('Memcached could not establish a connection');
        }

        $testConnection->quit();
    }

    public function test_memcached_locks_can_be_acquired_and_released()
    {
        Cache::store('memcached')->lock('foo')->forceRelease();
        $this->assertTrue(Cache::store('memcached')->lock('foo', 10)->get());
        $this->assertFalse(Cache::store('memcached')->lock('foo', 10)->get());
        Cache::store('memcached')->lock('foo')->forceRelease();
        $this->assertTrue(Cache::store('memcached')->lock('foo', 10)->get());
        $this->assertFalse(Cache::store('memcached')->lock('foo', 10)->get());
        Cache::store('memcached')->lock('foo')->forceRelease();
    }

    public function test_memcached_locks_can_block_for_seconds()
    {
        Carbon::setTestNow();

        Cache::store('memcached')->lock('foo')->forceRelease();
        $this->assertEquals('taylor', Cache::store('memcached')->lock('foo', 10)->block(1, function () {
            return 'taylor';
        }));

        Cache::store('memcached')->lock('foo')->release();
        $this->assertTrue(Cache::store('memcached')->lock('foo', 10)->block(1));
    }

    public function test_locks_can_run_callbacks()
    {
        Cache::store('memcached')->lock('foo')->forceRelease();
        $this->assertEquals('taylor', Cache::store('memcached')->lock('foo', 10)->get(function () {
            return 'taylor';
        }));
    }

    /**
     * @expectedException \Illuminate\Contracts\Cache\LockTimeoutException
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

    public function test_concurrent_memcached_locks_are_released_safely()
    {
        Cache::store('memcached')->lock('bar')->forceRelease();

        $firstLock = Cache::store('memcached')->lock('bar', 1);
        $this->assertTrue($firstLock->acquire());
        sleep(2);

        $secondLock = Cache::store('memcached')->lock('bar', 10);
        $this->assertTrue($secondLock->acquire());

        $firstLock->release();

        $this->assertTrue(Cache::store('memcached')->has('bar'));
    }

    public function test_memcached_locks_can_be_released_using_owner_token()
    {
        Cache::store('memcached')->lock('foo')->forceRelease();

        $firstLock = Cache::store('memcached')->lock('foo', 10);
        $this->assertTrue($firstLock->get());
        $owner = $firstLock->getOwner();

        $secondLock = Cache::store('memcached')->lock('foo', 10, $owner);
        $secondLock->release();

        $this->assertTrue(Cache::store('memcached')->lock('foo')->get());
    }
}
