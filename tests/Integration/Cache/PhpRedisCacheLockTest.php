<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Cache\RedisStore;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Orchestra\Testbench\TestCase;

class PhpRedisCacheLockTest extends TestCase
{
    use InteractsWithRedis;

    protected function tearDown(): void
    {
        $this->tearDownRedis();

        parent::tearDown();
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testPhpRedisLockCanBeAcquiredAndReleased($connection)
    {
        $repository = $this->getRepository($connection);

        $repository->lock('foo')->forceRelease();
        $this->assertNull($repository->lockConnection()->get($repository->getPrefix().'foo'));
        $lock = $repository->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse($repository->lock('foo', 10)->get());
        $lock->release();
        $this->assertNull($repository->lockConnection()->get($repository->getPrefix().'foo'));
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
