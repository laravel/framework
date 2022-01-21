<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\CacheBasedMaintenanceMode;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class FoundationCacheBasedMaintenanceModeTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_it_determines_whether_maintenance_mode_is_active()
    {
        $cache = m::mock(Factory::class, Repository::class);
        $cache->shouldReceive('store')->with('store-key')->andReturnSelf();

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key');

        $cache->shouldReceive('has')->once()->with('key')->andReturnFalse();
        $this->assertFalse($manager->active());

        $cache->shouldReceive('has')->once()->with('key')->andReturnTrue();
        $this->assertTrue($manager->active());
    }

    public function test_it_retrieves_payload_from_cache()
    {
        $cache = m::mock(Factory::class, Repository::class);
        $cache->shouldReceive('store')->with('store-key')->andReturnSelf();

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key');

        $cache->shouldReceive('get')->once()->with('key')->andReturn(['payload']);
        $this->assertSame(['payload'], $manager->data());
    }

    public function test_it_stores_payload_in_cache()
    {
        $cache = m::spy(Factory::class, Repository::class);
        $cache->shouldReceive('store')->with('store-key')->andReturnSelf();

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key');
        $manager->activate(['payload']);

        $cache->shouldHaveReceived('put')->once()->with('key', ['payload']);
    }

    public function test_it_removes_payload_from_cache()
    {
        $cache = m::spy(Factory::class, Repository::class);
        $cache->shouldReceive('store')->with('store-key')->andReturnSelf();

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key');
        $manager->deactivate();

        $cache->shouldHaveReceived('forget')->once()->with('key');
    }
}
