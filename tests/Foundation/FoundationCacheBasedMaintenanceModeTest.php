<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\CacheBasedMaintenanceMode;
use Illuminate\Foundation\Events\MaintenanceModeDisabled;
use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\TestCase;

class FoundationCacheBasedMaintenanceModeTest extends TestCase
{
    public function test_it_determines_whether_maintenance_mode_is_active()
    {
        $cache = Mockery::mock(Factory::class, Repository::class);
        $cache->expects('store')->times(2)->with('store-key')->andReturnSelf();

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key');

        $cache->expects('get')->with('key')->andReturnNull();
        $this->assertFalse($manager->active());

        $cache->expects('get')->with('key')->andReturn(['payload']);
        $this->assertTrue($manager->active());
    }

    public function test_it_retrieves_payload_from_cache()
    {
        $cache = Mockery::mock(Factory::class, Repository::class);
        $cache->expects('store')->with('store-key')->andReturnSelf();

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key');

        $cache->expects('get')->with('key')->andReturn(['payload']);
        $this->assertSame(['payload'], $manager->data());
    }

    public function test_it_stores_payload_in_cache()
    {
        $cache = Mockery::spy(Factory::class, Repository::class);
        $cache->expects('store')->with('store-key')->andReturnSelf();

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key');
        $manager->activate(['payload']);

        $cache->shouldHaveReceived('put')->once()->with('key', ['payload']);
    }

    public function test_it_removes_payload_from_cache()
    {
        $cache = Mockery::spy(Factory::class, Repository::class);
        $cache->expects('store')->with('store-key')->andReturnSelf();

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key');
        $manager->deactivate();

        $cache->shouldHaveReceived('forget')->once()->with('key');
    }

    public function test_it_removes_payload_from_cache_once_expired()
    {
        Carbon::setTestNow('2026-01-01 00:01:00');

        $cache = Mockery::spy(Factory::class, Repository::class);
        $cache->allows('store')->with('store-key')->andReturnSelf();
        $cache->allows('get')->with('key')->andReturn(['expires_at' => 1767225660]);
        $cache->allows('add')->with('key:expired:1767225660', true, 60)->andReturnTrue();

        $events = Mockery::spy(Dispatcher::class);

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key', $events);

        $this->assertFalse($manager->active());

        $cache->shouldHaveReceived('forget')->once()->with('key');
        $events->shouldHaveReceived('dispatch')->once()->with(Mockery::type(MaintenanceModeDisabled::class));
    }

    public function test_it_lets_a_single_process_remove_the_expired_payload()
    {
        Carbon::setTestNow('2026-01-01 00:01:00');

        $cache = Mockery::spy(Factory::class, Repository::class);
        $cache->allows('store')->with('store-key')->andReturnSelf();
        $cache->allows('get')->with('key')->andReturn(['expires_at' => 1767225660]);
        $cache->allows('add')->with('key:expired:1767225660', true, 60)->andReturnFalse();

        $events = Mockery::spy(Dispatcher::class);

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key', $events);

        $this->assertFalse($manager->active());

        $cache->shouldNotHaveReceived('forget');
        $events->shouldNotHaveReceived('dispatch');
    }
}
