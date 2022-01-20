<?php

namespace Illuminate\Tests\Integration\Cache;

use Exception;
use Illuminate\Cache\RedisStore;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;

class RedisCacheLockTest extends TestCase
{
    use InteractsWithRedis;

    protected function tearDown(): void
    {
        $this->tearDownRedis();
        Carbon::setTestNow(false);

        parent::tearDown();
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testRedisLocksCanBeAcquiredAndReleased($connection)
    {
        $repository = $this->getRepository($connection);
        $repository->lock('foo')->forceRelease();

        $lock = $repository->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse($repository->lock('foo', 10)->get());
        $lock->release();

        $lock = $repository->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse($repository->lock('foo', 10)->get());
        $repository->lock('foo')->release();
    }

    public function testRedisLockCanHaveASeparateConnection()
    {
        $this->app['config']->set('cache.stores.redis.lock_connection', 'default');

        $this->app['redis'] = $this->getRedisManager('phpredis');

        $this->assertSame('default', Cache::store('redis')->lock('foo')->getConnectionName());
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testRedisLocksCanBlockForSeconds($connection)
    {
        $repository = $this->getRepository($connection);
        Carbon::setTestNow();

        $repository->lock('foo')->forceRelease();
        $this->assertSame('taylor', $repository->lock('foo', 10)->block(1, function () {
            return 'taylor';
        }));

        $repository->lock('foo')->forceRelease();
        $this->assertTrue($repository->lock('foo', 10)->block(1));
    }

    /**
     * @dataProvider redisConnectionDataProvider
     */
    public function testConcurrentRedisLocksAreReleasedSafely($connection)
    {
        $repository = $this->getRepository($connection);
        $repository->lock('foo')->forceRelease();

        $firstLock = $repository->lock('foo', 1);
        $this->assertTrue($firstLock->get());
        usleep(1100000);

        $secondLock = $repository->lock('foo', 10);
        $this->assertTrue($secondLock->get());

        $firstLock->release();

        $this->assertFalse($repository->lock('foo')->get());
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testRedisLocksWithFailedBlockCallbackAreReleased($connection)
    {
        $repository = $this->getRepository($connection);
        $repository->lock('foo')->forceRelease();

        $firstLock = $repository->lock('foo', 10);

        try {
            $firstLock->block(1, function () {
                throw new Exception('failed');
            });
        } catch (Exception $e) {
            // Not testing the exception, just testing the lock
            // is released regardless of the how the exception
            // thrown by the callback was handled.
        }

        $secondLock = $repository->lock('foo', 1);

        $this->assertTrue($secondLock->get());
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testRedisLocksCanBeReleasedUsingOwnerToken($connection)
    {
        $repository = $this->getRepository($connection);
        $repository->lock('foo')->forceRelease();

        $firstLock = $repository->lock('foo', 10);
        $this->assertTrue($firstLock->get());
        $owner = $firstLock->owner();

        $secondLock = $repository->restoreLock('foo', $owner);
        $secondLock->release();

        $this->assertTrue($repository->lock('foo')->get());
    }

    /**
     * Builds a cache repository out of a predefined redis connection name.
     *
     * @param  string  $connection
     * @return \Illuminate\Cache\Repository
     */
    private function getRepository($connection)
    {
        /** @var \Illuminate\Cache\CacheManager $cacheManager */
        $cacheManager = $this->app->get('cache');

        return $cacheManager->repository(new RedisStore($this->getRedisManager($connection)));
    }
}
