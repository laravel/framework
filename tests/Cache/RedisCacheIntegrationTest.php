<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RedisCacheIntegrationTest extends TestCase
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

    /**
     * @param  string  $driver
     */
    #[DataProvider('redisDriverProvider')]
    public function testRedisCacheAddTwice($driver)
    {
        $store = new RedisStore($this->redis[$driver]);
        $repository = new Repository($store);
        $this->assertTrue($repository->add('k', 'v', 3600));
        $this->assertFalse($repository->add('k', 'v', 3600));
        $this->assertGreaterThan(3500, $this->redis[$driver]->connection()->ttl('k'));
    }

    /**
     * @param  string  $driver
     */
    #[DataProvider('redisDriverProvider')]
    public function testRedisCacheRateLimiter($driver)
    {
        $store = new RedisStore($this->redis[$driver]);
        $repository = new Repository($store);
        $rateLimiter = new RateLimiter($repository);

        $this->assertFalse($rateLimiter->tooManyAttempts('key', 1));
        $this->assertEquals(1, $rateLimiter->hit('key', 60));
        $this->assertTrue($rateLimiter->tooManyAttempts('key', 1));
        $this->assertFalse($rateLimiter->tooManyAttempts('key', 2));
    }

    /**
     * Breaking change.
     *
     * @param  string  $driver
     */
    #[DataProvider('redisDriverProvider')]
    public function testRedisCacheAddFalse($driver)
    {
        $store = new RedisStore($this->redis[$driver]);
        $repository = new Repository($store);
        $repository->forever('k', false);
        $this->assertFalse($repository->add('k', 'v', 60));
        $this->assertEquals(-1, $this->redis[$driver]->connection()->ttl('k'));
    }

    /**
     * Breaking change.
     *
     * @param  string  $driver
     */
    #[DataProvider('redisDriverProvider')]
    public function testRedisCacheAddNull($driver)
    {
        $store = new RedisStore($this->redis[$driver]);
        $repository = new Repository($store);
        $repository->forever('k', null);
        $this->assertFalse($repository->add('k', 'v', 60));
    }
}
