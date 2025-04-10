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
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Sleep;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class StackStoreTest extends TestCase
{
    use InteractsWithRedis;

    /** {@inheritdoc} */
    #[\Override]
    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            $this->setUpRedis();
        });

        $this->beforeApplicationDestroyed(function () {
            $this->tearDownRedis();
        });

        parent::setUp();
    }

    public function test_get()
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

        $cache = Cache::store('stack');
        dd($cache);
    }
}
