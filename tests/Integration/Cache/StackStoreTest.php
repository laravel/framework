<?php

namespace Illuminate\Tests\Integration\Cache;

use DateTime;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\StackStore;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Sleep;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Mockery as m;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;

#[WithMigration('cache')]
class StackStoreTest extends DatabaseTestCase
{
    use InteractsWithRedis;

    /** {@inheritdoc} */
    #[\Override]
    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            $this->setUpRedis();
            Redis::flushAll();
            DB::table('cache')->truncate();
        });

        $this->beforeApplicationDestroyed(function () {
            $this->tearDownRedis();
        });

        parent::setUp();
    }

    public function test_get_traverses_cache_to_retrieve_value()
    {
        Config::set('cache.stores.stack', [
            'driver' => 'stack',
        ]);
        Cache::extend('stack', function () {
            return new StackStore(new Collection([
                Cache::store('redis'),
                Cache::store('database'),
            ]));
        });

        Cache::store('redis')->forget('name');
        Cache::store('database')->put('name', 'Taylor');

        $value = Cache::driver('stack')->get('name');

        $this->assertSame('Taylor', $value);
    }

    public function test_get_updates_higher_stacks()
    {
        Config::set('cache.stores.stack', [
            'driver' => 'stack',
        ]);
        Cache::extend('stack', function () {
            return new StackStore(new Collection([
                Cache::store('redis'),
                Cache::store('database'),
            ]));
        });

        Cache::store('redis')->forget('name');
        Cache::store('database')->put('name', 'Taylor');

        $stackValue = Cache::driver('stack')->get('name');
        $databaseValue = Cache::driver('database')->get('name');
        $redisValue = Cache::driver('redis')->get('name');

        $this->assertSame('Taylor', $stackValue);
        $this->assertSame('Taylor', $databaseValue);
        $this->assertSame('Taylor', $redisValue);
    }

    public function test_get_updates_higher_stacks_with_TTL_from_source_cache()
    {
        $this->freezeTime();
        Config::set('cache.stores.stack', [
            'driver' => 'stack',
        ]);
        Cache::extend('stack', function () {
            return new StackStore(new Collection([
                Cache::store('redis'),
                Cache::store('database'),
            ]));
        });
        $prefix = Cache::driver('redis')->getPrefix();

        Cache::store('redis')->forget('name');
        Cache::store('database')->put('name', 'Taylor', 60);

        $value = Cache::driver('stack')->get('name');
        $databaseTTL = DB::table('cache')->value('expiration');
        $redisTTL = Cache::store("redis")->connection()->ttl("laravel_cache_name");

        $this->assertSame('Taylor', $value);
        $this->assertSame(now()->addSeconds(60)->getTimestamp(), $databaseTTL);
        $this->assertSame(60, $redisTTL);
    }
}
