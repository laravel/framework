<?php

use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository;
use Illuminate\Redis\PredisDatabase;
use Mockery as m;

class RedisCacheTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PredisDatabase
     */
    private $redis;

    public function setUp()
    {
        parent::setUp();

        $host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = getenv('REDIS_PORT') ?: 6379;

        $this->redis = new PredisDatabase([
            'cluster' => false,
            'default' => [
                'host' => $host,
                'port' => $port,
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);

        $this->redis->connection()->flushdb();
    }

    public function tearDown()
    {
        parent::tearDown();
        m::close();
        $this->redis->connection()->flushdb();
    }

    public function testRedisCacheAddTwice()
    {
        $store = new RedisStore($this->redis);
        $repository = new Repository($store);
        $this->assertTrue($repository->add('k', 'v', 60));
        $this->assertFalse($repository->add('k', 'v', 60));
    }

    /**
     * Breaking change.
     */
    public function testRedisCacheAddFalse()
    {
        $store = new RedisStore($this->redis);
        $repository = new Repository($store);
        $repository->forever('k', false);
        $this->assertFalse($repository->add('k', 'v', 60));
    }

    /**
     * Breaking change.
     */
    public function testRedisCacheAddNull()
    {
        $store = new RedisStore($this->redis);
        $repository = new Repository($store);
        $repository->forever('k', null);
        $this->assertFalse($repository->add('k', 'v', 60));
    }
}
