<?php

namespace Illuminate\Tests\Integration\Cache;

use Exception;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;

class RedisCacheLockTest extends TestCase
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

    public function testRedisLocksCanBeAcquiredAndReleased()
    {
        Cache::store('redis')->lock('foo')->forceRelease();

        $lock = Cache::store('redis')->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse(Cache::store('redis')->lock('foo', 10)->get());
        $lock->release();

        $lock = Cache::store('redis')->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse(Cache::store('redis')->lock('foo', 10)->get());
        Cache::store('redis')->lock('foo')->release();
    }

    public function testRedisLockCanHaveASeparateConnection()
    {
        $this->app['config']->set('cache.stores.redis.lock_connection', 'default');

        $this->assertSame('default', Cache::store('redis')->lock('foo')->getConnectionName());
    }

    public function testRedisLocksCanBlockForSeconds()
    {
        Cache::store('redis')->lock('foo')->forceRelease();
        $this->assertSame('taylor', Cache::store('redis')->lock('foo', 10)->block(1, function () {
            return 'taylor';
        }));

        Cache::store('redis')->lock('foo')->forceRelease();
        $this->assertTrue(Cache::store('redis')->lock('foo', 10)->block(1));
    }

    public function testConcurrentRedisLocksAreReleasedSafely()
    {
        Cache::store('redis')->lock('foo')->forceRelease();

        $firstLock = Cache::store('redis')->lock('foo', 1);
        $this->assertTrue($firstLock->get());
        sleep(2);

        $secondLock = Cache::store('redis')->lock('foo', 10);
        $this->assertTrue($secondLock->get());

        $firstLock->release();

        $this->assertFalse(Cache::store('redis')->lock('foo')->get());
    }

    public function testRedisLocksWithFailedBlockCallbackAreReleased()
    {
        Cache::store('redis')->lock('foo')->forceRelease();

        $firstLock = Cache::store('redis')->lock('foo', 10);

        try {
            $firstLock->block(1, function () {
                throw new Exception('failed');
            });
        } catch (Exception) {
            // Not testing the exception, just testing the lock
            // is released regardless of the how the exception
            // thrown by the callback was handled.
        }

        $secondLock = Cache::store('redis')->lock('foo', 1);

        $this->assertTrue($secondLock->get());
    }

    public function testRedisLocksCanBeReleasedUsingOwnerToken()
    {
        Cache::store('redis')->lock('foo')->forceRelease();

        $firstLock = Cache::store('redis')->lock('foo', 10);
        $this->assertTrue($firstLock->get());
        $owner = $firstLock->owner();

        $secondLock = Cache::store('redis')->restoreLock('foo', $owner);
        $secondLock->release();

        $this->assertTrue(Cache::store('redis')->lock('foo')->get());
    }

    public function testOwnerStatusCanBeCheckedAfterRestoringLock()
    {
        Cache::store('redis')->lock('foo')->forceRelease();

        $firstLock = Cache::store('redis')->lock('foo', 10);
        $this->assertTrue($firstLock->get());
        $owner = $firstLock->owner();

        $secondLock = Cache::store('redis')->restoreLock('foo', $owner);
        $this->assertTrue($secondLock->isOwnedByCurrentProcess());
    }

    public function testOtherOwnerDoesNotOwnLockAfterRestore()
    {
        Cache::store('redis')->lock('foo')->forceRelease();

        $firstLock = Cache::store('redis')->lock('foo', 10);
        $this->assertTrue($firstLock->isOwnedBy(null));
        $this->assertTrue($firstLock->get());
        $this->assertTrue($firstLock->isOwnedBy($firstLock->owner()));

        $secondLock = Cache::store('redis')->restoreLock('foo', 'other_owner');
        $this->assertTrue($secondLock->isOwnedBy($firstLock->owner()));
        $this->assertFalse($secondLock->isOwnedByCurrentProcess());
    }
}
