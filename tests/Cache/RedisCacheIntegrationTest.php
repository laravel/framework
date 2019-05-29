<?php

namespace Illuminate\Tests\Cache;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;

class RedisCacheIntegrationTest extends TestCase
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
        m::close();
        $this->tearDownRedis();
    }

    /**
     * @dataProvider redisDriverProvider
     *
     * @param string $driver
     */
    public function testRedisCacheAddTwice($driver)
    {
        $store = new RedisStore($this->redis[$driver]);
        $repository = new Repository($store);
        $this->assertTrue($repository->add('k', 'v', 60));
        $this->assertFalse($repository->add('k', 'v', 60));
        $this->assertGreaterThan(3500, $this->redis[$driver]->connection()->ttl('k'));
    }

    /**
     * Breaking change.
     *
     * @dataProvider redisDriverProvider
     *
     * @param string $driver
     */
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
     * @dataProvider redisDriverProvider
     *
     * @param string $driver
     */
    public function testRedisCacheAddNull($driver)
    {
        $store = new RedisStore($this->redis[$driver]);
        $repository = new Repository($store);
        $repository->forever('k', null);
        $this->assertFalse($repository->add('k', 'v', 60));
    }
}
