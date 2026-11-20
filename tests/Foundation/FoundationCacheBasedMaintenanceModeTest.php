<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\CacheBasedMaintenanceMode;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;

class FoundationCacheBasedMaintenanceModeTest extends TestCase
{
    use VerifiesDoubles;

    public function test_it_determines_whether_maintenance_mode_is_active()
    {
        $cache = Double::for(Factory::class, Repository::class);
        $cache->expects('store')->times(2)->with('store-key')->returns($cache);

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key');

        $cache->expects('has')->with('key')->times(2)->returns(false, true);

        $this->assertFalse($manager->active());
        $this->assertTrue($manager->active());
    }

    public function test_it_retrieves_payload_from_cache()
    {
        $cache = Double::for(Factory::class, Repository::class);
        $cache->expects('store')->with('store-key')->returns($cache);

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key');

        $cache->expects('get')->with('key')->returns(['payload']);
        $this->assertSame(['payload'], $manager->data());
    }

    public function test_it_stores_payload_in_cache()
    {
        $cache = Double::for(Factory::class, Repository::class);
        $cache->expects('store')->with('store-key')->returns($cache);

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key');
        $manager->activate(['payload']);

        $cache->received('put')->times(1)->with('key', ['payload']);
    }

    public function test_it_removes_payload_from_cache()
    {
        $cache = Double::for(Factory::class, Repository::class);
        $cache->expects('store')->with('store-key')->returns($cache);

        $manager = new CacheBasedMaintenanceMode($cache, 'store-key', 'key');
        $manager->deactivate();

        $cache->received('forget')->times(1)->with('key');
    }
}
