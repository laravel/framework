<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\CacheBasedMaintenanceMode;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class FoundationCacheBasedMaintenanceModeTest extends TestCase
{
    public function testItDeterminesWhetherMaintenanceModeIsActive()
    {
        $cache = m::mock(Factory::class, Repository::class);
        $cache->shouldReceive('store')->with('store-key')->andReturnSelf();

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key');

        $cache->shouldReceive('has')->once()->with('key')->andReturnFalse();
        $this->assertFalse($manager->active());

        $cache->shouldReceive('has')->once()->with('key')->andReturnTrue();
        $this->assertTrue($manager->active());
    }

    public function testItRetrievesPayloadFromCache()
    {
        $cache = m::mock(Factory::class, Repository::class);
        $cache->shouldReceive('store')->with('store-key')->andReturnSelf();

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key');

        $cache->shouldReceive('get')->once()->with('key')->andReturn(['payload']);
        $this->assertSame(['payload'], $manager->data());
    }

    public function testItStoresPayloadInCache()
    {
        $cache = m::spy(Factory::class, Repository::class);
        $cache->shouldReceive('store')->with('store-key')->andReturnSelf();

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key');
        $manager->activate(['payload']);

        $cache->shouldHaveReceived('put')->once()->with('key', ['payload']);
    }

    public function testItRemovesPayloadFromCache()
    {
        $cache = m::spy(Factory::class, Repository::class);
        $cache->shouldReceive('store')->with('store-key')->andReturnSelf();

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key');
        $manager->deactivate();

        $cache->shouldHaveReceived('forget')->once()->with('key');
    }
}
