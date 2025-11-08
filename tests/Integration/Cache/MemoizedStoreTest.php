<?php

namespace Illuminate\Tests\Integration\Cache;

use BadMethodCallException;
use Illuminate\Cache\Events\CacheEvent;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\ForgettingKey;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\RetrievingKey;
use Illuminate\Cache\Events\RetrievingManyKeys;
use Illuminate\Cache\Events\WritingKey;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Redis;
use Orchestra\Testbench\TestCase;
use Throwable;

class MemoizedStoreTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();

        Config::set('cache.default', 'redis');
        Redis::flushAll();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownRedis();
    }

    public function test_it_can_memoize_when_retrieving_single_value()
    {
        Cache::put('name', 'Tim', 60);

        $live = Cache::get('name');
        $memoized = Cache::memo()->get('name');
        $this->assertSame('Tim', $live);
        $this->assertSame('Tim', $memoized);

        Cache::put('name', 'Taylor', 60);

        $live = Cache::get('name');
        $memoized = Cache::memo()->get('name');
        $this->assertSame('Taylor', $live);
        $this->assertSame('Tim', $memoized);
    }

    public function test_null_values_are_memoized_when_retrieving_single_value()
    {
        $live = Cache::get('name');
        $memoized = Cache::memo()->get('name');
        $this->assertNull($live);
        $this->assertNull($memoized);

        Cache::put('name', 'Taylor', 60);

        $live = Cache::get('name');
        $memoized = Cache::memo()->get('name');
        $this->assertSame('Taylor', $live);
        $this->assertNull($memoized);
    }

    public function test_it_can_memoize_when_retrieving_multiple_values()
    {
        Cache::put('name.0', 'Tim', 60);
        Cache::put('name.1', 'Taylor', 60);

        $live = Cache::getMultiple(['name.0', 'name.1']);
        $memoized = Cache::memo()->getMultiple(['name.0', 'name.1']);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Taylor'], $live);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Taylor'], $memoized);

        Cache::put('name.0', 'MacDonald', 60);
        Cache::put('name.1', 'Otwell', 60);

        $live = Cache::getMultiple(['name.0', 'name.1']);
        $memoized = Cache::memo()->getMultiple(['name.0', 'name.1']);
        $this->assertSame(['name.0' => 'MacDonald', 'name.1' => 'Otwell'], $live);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Taylor'], $memoized);
    }

    public function test_it_uses_correct_keys_for_getMultiple()
    {
        $data = [
            'a' => 'string-value',
            '1.1' => 'float-value',
            '1' => 'integer-value-as-string',
            2 => 'integer-value',
        ];
        Cache::putMany($data);

        $memoValue = Cache::memo()->many(['a', '1.1', '1', 2]);
        $cacheValue = Cache::many(['a', '1.1', '1', 2]);

        $this->assertSame([
            'a' => 'string-value',
            '1.1' => 'float-value',
            '1' => 'integer-value-as-string',
            2 => 'integer-value',
        ], $cacheValue);
        $this->assertSame($cacheValue, $memoValue);

        // ensure correct on the second memoized retrieval
        $memoValue = Cache::memo()->many(['a', '1.1', '1', 2]);

        $this->assertSame($cacheValue, $memoValue);
    }

    public function test_null_values_are_memoized_when_retrieving_multiple_values()
    {
        $live = Cache::getMultiple(['name.0', 'name.1']);
        $memoized = Cache::memo()->getMultiple(['name.0', 'name.1']);
        $this->assertSame($live, ['name.0' => null, 'name.1' => null]);
        $this->assertSame($memoized, ['name.0' => null, 'name.1' => null]);

        Cache::put('name.0', 'MacDonald', 60);
        Cache::put('name.1', 'Otwell', 60);

        $live = Cache::getMultiple(['name.0', 'name.1']);
        $memoized = Cache::memo()->getMultiple(['name.0', 'name.1']);
        $this->assertSame($live, ['name.0' => 'MacDonald', 'name.1' => 'Otwell']);
        $this->assertSame($memoized, ['name.0' => null, 'name.1' => null]);
    }

    public function test_it_can_retrieve_already_memoized_and_not_yet_memoized_values_when_retrieving_multiple_values()
    {
        Cache::put('name.0', 'Tim', 60);
        Cache::put('name.1', 'Taylor', 60);

        $live = Cache::get('name.0');
        $memoized = Cache::memo()->get('name.0');
        $this->assertSame('Tim', $live);
        $this->assertSame('Tim', $memoized);

        Cache::put('name.0', 'MacDonald', 60);
        Cache::put('name.1', 'Otwell', 60);

        $live = Cache::getMultiple(['name.0', 'name.1']);
        $memoized = Cache::memo()->getMultiple(['name.0', 'name.1']);
        $this->assertSame(['name.0' => 'MacDonald', 'name.1' => 'Otwell'], $live);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Otwell'], $memoized);
    }

    public function test_put_forgets_memoized_value()
    {
        Cache::put(['name.0' => 'Tim', 'name.1' => 'Taylor'], 60);

        $live = Cache::get(['name.0', 'name.1']);
        $memoized = Cache::memo()->get(['name.0', 'name.1']);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Taylor'], $live);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Taylor'], $memoized);

        Cache::memo()->put('name.0', 'MacDonald');
        Cache::memo()->put('name.1', 'Otwell');

        $live = Cache::get(['name.0', 'name.1']);
        $memoized = Cache::memo()->get(['name.0', 'name.1']);
        $this->assertSame(['name.0' => 'MacDonald', 'name.1' => 'Otwell'], $live);
        $this->assertSame(['name.0' => 'MacDonald', 'name.1' => 'Otwell'], $memoized);
    }

    public function test_put_many_forgets_memoized_value()
    {
        Cache::memo()->put(['name.0' => 'Tim', 'name.1' => 'Taylor'], 60);

        $live = Cache::get(['name.0', 'name.1']);
        $memoized = Cache::memo()->get(['name.0', 'name.1']);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Taylor'], $live);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Taylor'], $memoized);

        Cache::memo()->put(['name.0' => 'MacDonald'], 60);

        $live = Cache::get(['name.0', 'name.1']);
        $memoized = Cache::memo()->get(['name.0', 'name.1']);
        $this->assertSame(['name.0' => 'MacDonald', 'name.1' => 'Taylor'], $live);
        $this->assertSame(['name.0' => 'MacDonald', 'name.1' => 'Taylor'], $memoized);
    }

    public function test_increment_forgets_memoized_value()
    {
        Cache::put('count', 1, 60);

        $live = Cache::get('count');
        $memoized = Cache::memo()->get('count');
        $this->assertSame('1', $live);
        $this->assertSame('1', $memoized);

        Cache::memo()->increment('count');

        $live = Cache::get('count');
        $memoized = Cache::memo()->get('count');
        $this->assertSame('2', $live);
        $this->assertSame('2', $memoized);
    }

    public function test_decrement_forgets_memoized_value()
    {
        Cache::put('count', 1, 60);

        $live = Cache::get('count');
        $memoized = Cache::memo()->get('count');
        $this->assertSame('1', $live);
        $this->assertSame('1', $memoized);

        Cache::memo()->decrement('count');

        $live = Cache::get('count');
        $memoized = Cache::memo()->get('count');
        $this->assertSame('0', $live);
        $this->assertSame('0', $memoized);
    }

    public function test_forever_forgets_memoized_value()
    {
        Cache::put('name', 'Tim', 60);

        $live = Cache::get('name');
        $memoized = Cache::memo()->get('name');
        $this->assertSame('Tim', $live);
        $this->assertSame('Tim', $memoized);

        Cache::memo()->forever('name', 'Taylor');

        $live = Cache::get('name');
        $memoized = Cache::memo()->get('name');
        $this->assertSame('Taylor', $live);
        $this->assertSame('Taylor', $memoized);
    }

    public function test_forget_forgets_memoized_value()
    {
        Cache::put('name', 'Tim', 60);

        $live = Cache::get('name');
        $memoized = Cache::memo()->get('name');
        $this->assertSame('Tim', $live);
        $this->assertSame('Tim', $memoized);

        Cache::memo()->forget('name');

        $live = Cache::get('name');
        $memoized = Cache::memo()->get('name');
        $this->assertNull($live);
        $this->assertNull($memoized);
    }

    public function test_flush_forgets_memoized_value()
    {
        Cache::put(['name.0' => 'Tim', 'name.1' => 'Taylor'], 60);

        $live = Cache::get(['name.0', 'name.1']);
        $memoized = Cache::memo()->get(['name.0', 'name.1']);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Taylor'], $live);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Taylor'], $memoized);

        Cache::memo()->flush();

        $live = Cache::get(['name.0', 'name.1']);
        $memoized = Cache::memo()->get(['name.0', 'name.1']);
        $this->assertSame(['name.0' => null, 'name.1' => null], $live);
        $this->assertSame(['name.0' => null, 'name.1' => null], $memoized);
    }

    public function test_memoized_driver_uses_underlying_drivers_prefix()
    {
        $this->assertSame('laravel_cache_', Cache::memo()->getPrefix());

        Cache::driver('redis')->setPrefix('foo');

        $this->assertSame('foo', Cache::memo()->getPrefix());
    }

    public function test_memoized_keys_are_prefixed()
    {
        $redis = Cache::store('redis');

        $redis->setPrefix('aaaa');
        $redis->put('name', 'Tim', 60);
        $redis->setPrefix('zzzz');
        $redis->put('name', 'Taylor', 60);

        $redis->setPrefix('aaaa');
        $value = Cache::memo('redis')->get('name');
        $this->assertSame('Tim', $value);

        $redis->setPrefix('zzzz');
        $value = Cache::memo('redis')->get('name');
        $this->assertSame('Taylor', $value);
    }

    public function test_it_dispatches_decorated_driver_events_only()
    {
        $redis = Cache::driver('redis');
        $events = [];
        Event::listen('*', function ($type, $event) use (&$events) {
            if ($event[0] instanceof CacheEvent) {
                $events[] = $event[0];
            }
        });

        Cache::memo('redis')->get('name');
        $this->assertCount(2, $events);
        $this->assertInstanceOf(RetrievingKey::class, $events[0]);
        $this->assertSame('redis', $events[0]->storeName);
        $this->assertSame('name', $events[0]->key);
        $this->assertInstanceOf(CacheMissed::class, $events[1]);
        $this->assertSame('redis', $events[1]->storeName);
        $this->assertSame('name', $events[1]->key);
        Cache::memo('redis')->get('name');
        $this->assertCount(2, $events);

        Cache::memo('redis')->many(['name']);
        $this->assertCount(2, $events);

        Cache::memo('redis')->many(['name.0', 'name.1']);
        $this->assertCount(5, $events);
        $this->assertInstanceOf(RetrievingManyKeys::class, $events[2]);
        $this->assertSame('redis', $events[2]->storeName);
        $this->assertSame(['name.0', 'name.1'], $events[2]->keys);
        $this->assertInstanceOf(CacheMissed::class, $events[3]);
        $this->assertSame('redis', $events[3]->storeName);
        $this->assertSame('name.0', $events[3]->key);
        $this->assertInstanceOf(CacheMissed::class, $events[4]);
        $this->assertSame('redis', $events[4]->storeName);
        $this->assertSame('name.1', $events[4]->key);

        Cache::memo('redis')->many(['name.0', 'name.1']);
        $this->assertCount(5, $events);

        Cache::memo('redis')->put('name', 'Tim', 1);
        $this->assertCount(7, $events);
        $this->assertInstanceOf(WritingKey::class, $events[5]);
        $this->assertSame('redis', $events[5]->storeName);
        $this->assertSame('name', $events[5]->key);
        $this->assertInstanceOf(KeyWritten::class, $events[6]);
        $this->assertSame('redis', $events[6]->storeName);
        $this->assertSame('name', $events[6]->key);

        Cache::memo('redis')->putMany(['name.0' => 'Tim', 'name.1' => 'Taylor']);
        $this->assertCount(11, $events);
        $this->assertInstanceOf(WritingKey::class, $events[7]);
        $this->assertSame('redis', $events[7]->storeName);
        $this->assertSame('name.0', $events[7]->key);
        $this->assertInstanceOf(KeyWritten::class, $events[8]);
        $this->assertSame('redis', $events[8]->storeName);
        $this->assertSame('name.0', $events[8]->key);
        $this->assertInstanceOf(WritingKey::class, $events[9]);
        $this->assertSame('redis', $events[9]->storeName);
        $this->assertSame('name.1', $events[9]->key);
        $this->assertInstanceOf(KeyWritten::class, $events[10]);
        $this->assertSame('redis', $events[10]->storeName);
        $this->assertSame('name.1', $events[10]->key);

        Cache::memo('redis')->increment('count');
        $this->assertCount(11, $events);

        Cache::memo('redis')->decrement('count');
        $this->assertCount(11, $events);

        Cache::memo('redis')->forever('name', 'Taylor');
        $this->assertCount(13, $events);
        $this->assertInstanceOf(WritingKey::class, $events[11]);
        $this->assertSame('redis', $events[11]->storeName);
        $this->assertSame('name', $events[11]->key);
        $this->assertInstanceOf(KeyWritten::class, $events[12]);
        $this->assertSame('redis', $events[12]->storeName);
        $this->assertSame('name', $events[12]->key);

        Cache::memo('redis')->forget('name');
        $this->assertCount(15, $events);
        $this->assertInstanceOf(ForgettingKey::class, $events[13]);
        $this->assertSame('redis', $events[13]->storeName);
        $this->assertSame('name', $events[13]->key);
        $this->assertInstanceOf(KeyForgotten::class, $events[14]);
        $this->assertSame('redis', $events[14]->storeName);
        $this->assertSame('name', $events[14]->key);

        Cache::memo('redis')->flush();
        $this->assertCount(15, $events);
    }

    public function test_it_resets_cache_store_with_scoped_instances()
    {
        Cache::put('name', 'Tim', 60);

        $live = Cache::get('name');
        $memoized = Cache::memo()->get('name');
        $this->assertSame('Tim', $live);
        $this->assertSame('Tim', $memoized);

        Cache::put('name', 'Taylor', 60);
        $this->app->forgetScopedInstances();

        $live = Cache::get('name');
        $memoized = Cache::memo()->get('name');
        $this->assertSame('Taylor', $live);
        $this->assertSame('Taylor', $memoized);
    }

    public function test_it_throws_when_underlying_store_does_not_support_locks()
    {
        $this->freezeTime();
        $exceptions = [];
        Exceptions::reportable(function (Throwable $e) use (&$exceptions) {
            $exceptions[] = $e;
        });
        Config::set('cache.stores.no-lock', ['driver' => 'no-lock']);
        Cache::extend('no-lock', fn () => Cache::repository(new class implements Store
        {
            public function get($key)
            {
                return Cache::get(...func_get_args());
            }

            public function many(array $keys)
            {
                return Cache::many(...func_get_args());
            }

            public function put($key, $value, $seconds)
            {
                return Cache::put(...func_get_args());
            }

            public function putMany(array $values, $seconds)
            {
                return Cache::putMany(...func_get_args());
            }

            public function increment($key, $value = 1)
            {
                return Cache::increment(...func_get_args());
            }

            public function decrement($key, $value = 1)
            {
                return Cache::decrement(...func_get_args());
            }

            public function forever($key, $value)
            {
                return Cache::forever(...func_get_args());
            }

            public function forget($key)
            {
                return Cache::forget(...func_get_args());
            }

            public function flush()
            {
                return Cache::flush(...func_get_args());
            }

            public function getPrefix()
            {
                return Cache::getPrefix(...func_get_args());
            }
        }));
        Cache::flexible('key', [10, 20], 'value-1');

        $this->travel(11)->seconds();
        Cache::memo('no-lock')->flexible('key', [10, 20], 'value-2');
        defer()->invoke();
        $value = Cache::get('key');

        $this->assertCount(1, $exceptions);
        $this->assertInstanceOf(BadMethodCallException::class, $exceptions[0]);
        $this->assertSame('This cache store does not support locks.', $exceptions[0]->getMessage());
    }

    public function test_it_supports_with_flexible()
    {
        $this->freezeTime();
        Cache::flexible('key', [10, 20], 'value-1');

        $this->travel(11)->seconds();
        Cache::memo()->flexible('key', [10, 20], 'value-2');
        defer()->invoke();
        $value = Cache::get('key');

        $this->assertSame('value-2', $value);
    }
}
