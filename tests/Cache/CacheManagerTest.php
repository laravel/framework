<?php

use Mockery as m;
use Illuminate\Cache\CacheManager;
use Illuminate\Redis\Database as RedisDatabase;

class CacheManagerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testCustomDriverClosureBoundObjectIsCacheManager()
    {
        $cacheManager = new CacheManager([
            'config' => [
                'cache.stores.'.__CLASS__ => [
                    'driver' => __CLASS__,
                ],
            ],
        ]);
        $driver = function () {
            return $this;
        };
        $cacheManager->extend(__CLASS__, $driver);
        $this->assertEquals($cacheManager, $cacheManager->store(__CLASS__));
    }

    public function testRedisStoreHasCompressionDisabledByDefault()
    {
        $app = new Illuminate\Foundation\Application();
        $app['redis'] = new RedisDatabase();
        $app['config'] = [
            'cache.prefix' => '',
            'cache.default' => 'redis',
            'cache.stores.redis' => [
                'driver' => 'redis',
            ],
        ];
        $cacheManager = new CacheManager($app);
        $this->assertFalse($cacheManager->getUseCompression());
    }

    public function testRedisStoreCanBeConfiguredToUseCompression()
    {
        $app = new Illuminate\Foundation\Application();
        $app['redis'] = new RedisDatabase();
        $app['config'] = [
            'cache.prefix' => '',
            'cache.default' => 'redis',
            'cache.stores.redis' => [
                'driver' => 'redis',
                'use_compression' => true,
            ],
        ];
        $cacheManager = new CacheManager($app);
        $this->assertTrue($cacheManager->getUseCompression());
    }
}
