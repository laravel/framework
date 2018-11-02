<?php

namespace Illuminate\Tests\Cache;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Cache\ArrayStore;
use Illuminate\Config\Repository;
use Illuminate\Cache\CacheManager;
use Illuminate\Foundation\Application;

class CacheManagerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testCustomDriverClosureBoundObjectIsCacheManager()
    {
        $app = new Application();
        $config = new Repository();
        $config->set('cache.stores.'.__CLASS__, [
            'driver' => __CLASS__,
        ]);

        $app->instance('config', $config);
        $cacheManager = new CacheManager($app);
        $driver = function () {
            return $this;
        };
        $cacheManager->extend(__CLASS__, $driver);
        $this->assertEquals($cacheManager, $cacheManager->store(__CLASS__));
    }

    public function testForgetDriver()
    {
        $cacheManager = m::mock(CacheManager::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $cacheManager->shouldReceive('resolve')
            ->withArgs(['array'])
            ->times(4)
            ->andReturn(new ArrayStore());

        $cacheManager->shouldReceive('getDefaultDriver')
            ->once()
            ->andReturn('array');

        foreach (['array', ['array'], null] as $option) {
            $cacheManager->store('array');
            $cacheManager->store('array');
            $cacheManager->forgetDriver($option);
            $cacheManager->store('array');
            $cacheManager->store('array');
        }
    }

    public function testForgetDriverForgets()
    {
        $app = new Application();
        $config = new Repository();
        $config->set('cache.stores.forget', [
            'driver' => 'forget',
        ]);

        $app->instance('config', $config);

        $cacheManager = new CacheManager($app);
        $cacheManager->extend('forget', function () {
            return new ArrayStore();
        });

        $cacheManager->store('forget')->forever('foo', 'bar');
        $this->assertSame('bar', $cacheManager->store('forget')->get('foo'));
        $cacheManager->forgetDriver('forget');
        $this->assertNull($cacheManager->store('forget')->get('foo'));
    }
}
