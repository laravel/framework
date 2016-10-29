<?php

use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository;
use Mockery as m;

class RedisCacheTest extends PHPUnit_Framework_TestCase
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
