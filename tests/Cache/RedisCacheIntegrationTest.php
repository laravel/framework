<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use PHPUnit\Framework\TestCase;

class RedisCacheIntegrationTest extends TestCase
{
    use InteractsWithRedis;

    protected function tearDown(): void
    {
        $this->tearDownRedis();

        parent::tearDown();
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     *
     * @param  string  $connection
     */
    public function testRedisCacheAddTwice($connection)
    {
        $repository = $this->getRepository($connection);

        $this->assertTrue($repository->add('k', 'v', 3600));
        $this->assertFalse($repository->add('k', 'v', 3600));
        $this->assertGreaterThan(3500, $repository->getStore()->connection()->ttl('k'));
    }

    /**
     * Breaking change.
     *
     * @dataProvider extendedRedisConnectionDataProvider
     *
     * @param  string  $connection
     */
    public function testRedisCacheAddFalse($connection)
    {
        $repository = $this->getRepository($connection);

        $repository->forever('k', false);
        $this->assertFalse($repository->add('k', 'v', 60));
        $this->assertEquals(-1, $repository->getStore()->connection()->ttl('k'));
    }

    /**
     * Breaking change.
     *
     * @dataProvider extendedRedisConnectionDataProvider
     *
     * @param  string  $connection
     */
    public function testRedisCacheAddNull($connection)
    {
        $repository = $this->getRepository($connection);

        $repository->forever('k', null);
        $this->assertFalse($repository->add('k', 'v', 60));
    }

    /**
     * Builds a cache repository out of a predefined redis connection name.
     *
     * @param  string  $connection
     * @return \Illuminate\Cache\Repository
     */
    private function getRepository($connection)
    {
        return new Repository(new RedisStore($this->getRedisManager($connection)));
    }
}
