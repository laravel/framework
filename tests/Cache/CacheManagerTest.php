<?php

namespace Illuminate\Tests\Cache;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Cache\CacheManager;

class CacheManagerTest extends TestCase
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
}
