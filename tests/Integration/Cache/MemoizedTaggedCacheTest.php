<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Cache\Events\CacheEvent;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\RetrievingKey;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Orchestra\Testbench\TestCase;

class MemoizedTaggedCacheTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();

        Config::set('cache.default', 'redis');
        Redis::connection(Config::get('cache.stores.redis.connection'))->flushDb();
        Redis::connection(Config::get('cache.stores.redis.lock_connection'))->flushDb();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownRedis();
    }

    public function test_it_can_memoize_with_tags_when_retrieving_single_value()
    {
        Cache::tags(['foo', 'bar'])->put('name', 'Tim', 60);

        $live = Cache::tags(['foo', 'bar'])->get('name');
        $memoized = Cache::memo()->tags(['foo', 'bar'])->get('name');
        $this->assertSame('Tim', $live);
        $this->assertSame('Tim', $memoized);

        Cache::tags(['foo', 'bar'])->put('name', 'Taylor', 60);

        $live = Cache::tags(['foo', 'bar'])->get('name');
        $memoized = Cache::memo()->tags(['foo', 'bar'])->get('name');
        $this->assertSame('Taylor', $live);
        $this->assertSame('Tim', $memoized);
    }

    public function test_it_can_memoize_with_tags_when_retrieving_multiple_values()
    {
        Cache::tags(['foo', 'bar'])->put('name.0', 'Tim', 60);
        Cache::tags(['foo', 'bar'])->put('name.1', 'Taylor', 60);

        $live = Cache::tags(['foo', 'bar'])->getMultiple(['name.0', 'name.1']);
        $memoized = Cache::memo()->tags(['foo', 'bar'])->getMultiple(['name.0', 'name.1']);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Taylor'], $live);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Taylor'], $memoized);

        Cache::tags(['foo', 'bar'])->put('name.0', 'MacDonald', 60);
        Cache::tags(['foo', 'bar'])->put('name.1', 'Otwell', 60);

        $live = Cache::tags(['foo', 'bar'])->getMultiple(['name.0', 'name.1']);
        $memoized = Cache::memo()->tags(['foo', 'bar'])->getMultiple(['name.0', 'name.1']);
        $this->assertSame(['name.0' => 'MacDonald', 'name.1' => 'Otwell'], $live);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Taylor'], $memoized);
    }

    public function test_it_can_flush_memoized_values_with_tags()
    {
        Cache::tags(['foo', 'bar'])->put('name.0', 'Tim', 60);
        Cache::tags(['foo', 'bar'])->put('name.1', 'Taylor', 60);

        $live = Cache::tags(['foo', 'bar'])->getMultiple(['name.0', 'name.1']);
        $memoized = Cache::memo()->tags(['foo', 'bar'])->getMultiple(['name.0', 'name.1']);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Taylor'], $live);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Taylor'], $memoized);

        Cache::tags(['foo', 'bar'])->flush();

        $live = Cache::tags(['foo', 'bar'])->getMultiple(['name.0', 'name.1']);
        $memoized = Cache::memo()->tags(['foo', 'bar'])->getMultiple(['name.0', 'name.1']);
        $this->assertSame(['name.0' => null, 'name.1' => null], $live);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Taylor'], $memoized);

        Cache::memo()->tags(['foo', 'bar'])->flush();
        $memoized = Cache::memo()->tags(['foo', 'bar'])->getMultiple(['name.0', 'name.1']);
        $this->assertSame(['name.0' => null, 'name.1' => null], $memoized);
    }

    public function test_it_can_forget_memoized_values_with_tags()
    {
        Cache::tags(['foo', 'bar'])->put('name', 'Tim', 60);

        $live = Cache::tags(['foo', 'bar'])->get('name');
        $memoized = Cache::memo()->tags(['foo', 'bar'])->get('name');
        $this->assertSame('Tim', $live);
        $this->assertSame('Tim', $memoized);

        Cache::memo()->tags(['foo', 'bar'])->forget('name');

        $live = Cache::tags(['foo', 'bar'])->get('name');
        $memoized = Cache::memo()->tags(['foo', 'bar'])->get('name');
        $this->assertNull($live);
        $this->assertNull($memoized);
    }

    public function test_it_can_increment_and_decrement_memoized_values_with_tags()
    {
        Cache::tags(['foo', 'bar'])->put('count', 1, 60);

        $live = Cache::tags(['foo', 'bar'])->get('count');
        $memoized = Cache::memo()->tags(['foo', 'bar'])->get('count');
        $this->assertSame('1', $live);
        $this->assertSame('1', $memoized);

        Cache::memo()->tags(['foo', 'bar'])->increment('count');

        $live = Cache::tags(['foo', 'bar'])->get('count');
        $memoized = Cache::memo()->tags(['foo', 'bar'])->get('count');
        $this->assertSame('2', $live);
        $this->assertSame('2', $memoized);

        Cache::memo()->tags(['foo', 'bar'])->decrement('count');

        $live = Cache::tags(['foo', 'bar'])->get('count');
        $memoized = Cache::memo()->tags(['foo', 'bar'])->get('count');
        $this->assertSame('1', $live);
        $this->assertSame('1', $memoized);
    }

    public function test_put_many_for_tagged_memo_driver()
    {
        Cache::memo()->tags(['foo', 'bar'])->putMany(['name.0' => 'Tim', 'name.1' => 'Taylor'], 60);

        $live = Cache::tags(['foo', 'bar'])->getMultiple(['name.0', 'name.1']);
        $memoized = Cache::memo()->tags(['foo', 'bar'])->getMultiple(['name.0', 'name.1']);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Taylor'], $live);
        $this->assertSame(['name.0' => 'Tim', 'name.1' => 'Taylor'], $memoized);

        Cache::memo()->tags(['foo', 'bar'])->putMany(['name.0' => 'MacDonald', 'name.1' => 'Otwell'], 60);

        $live = Cache::tags(['foo', 'bar'])->getMultiple(['name.0', 'name.1']);
        $memoized = Cache::memo()->tags(['foo', 'bar'])->getMultiple(['name.0', 'name.1']);
        $this->assertSame(['name.0' => 'MacDonald', 'name.1' => 'Otwell'], $live);
        $this->assertSame(['name.0' => 'MacDonald', 'name.1' => 'Otwell'], $memoized);
    }

    public function test_tagged_memoized_cache_uses_prefixes()
    {
        Cache::tags(['foo', 'bar'])->setPrefix('prefix1_');

        $this->assertSame('prefix1_', Cache::tags(['foo', 'bar'])->getPrefix());
        $this->assertSame('prefix1_', Cache::memo()->tags(['foo', 'bar'])->getPrefix());
    }

    public function test_memoized_keys_are_prefixed_with_tags()
    {
        $redis = Cache::store('redis')->tags(['foo', 'bar']);

        $redis->setPrefix('aaaa');
        $redis->put('name', 'Tim', 60);
        $redis->setPrefix('zzzz');
        $redis->put('name', 'Taylor', 60);

        $redis->setPrefix('aaaa');
        $value = Cache::memo('redis')->tags(['foo', 'bar'])->get('name');
        $this->assertSame('Tim', $value);

        $redis->setPrefix('zzzz');
        $value = Cache::memo('redis')->tags(['foo', 'bar'])->get('name');
        $this->assertSame('Taylor', $value);
    }

    public function test_error_thrown_when_tags_not_supported()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('This cache store does not support tagging.');

        Cache::memo('file')->tags(['foo', 'bar'])->put('name', 'Tim', 60);
    }

    public function test_it_keeps_memoized_values_isolated_between_tag_sets()
    {
        Cache::tags(['foo'])->put('name', 'Foo', 60);
        Cache::tags(['bar'])->put('name', 'Bar', 60);

        $this->assertSame('Foo', Cache::memo()->tags(['foo'])->get('name'));
        $this->assertSame('Bar', Cache::memo()->tags(['bar'])->get('name'));
    }

    public function test_scalar_and_array_tag_names_share_the_same_memoized_cache()
    {
        Cache::tags(['foo'])->put('name', 'Foo', 60);

        $this->assertSame('Foo', Cache::memo()->tags('foo')->get('name'));

        Cache::tags(['foo'])->put('name', 'Bar', 60);

        $this->assertSame('Foo', Cache::memo()->tags(['foo'])->get('name'));
    }

    public function test_it_flushes_tagged_memoized_values_when_the_store_is_flushed()
    {
        Cache::tags(['foo'])->put('name', 'Foo', 60);
        Cache::memo()->tags(['foo'])->get('name');

        Cache::memo()->flush();

        $this->assertNull(Cache::memo()->tags(['foo'])->get('name'));
    }

    public function test_it_resolves_defaults_when_retrieving_multiple_tagged_values()
    {
        $this->assertSame(
            ['missing' => 'fallback'],
            Cache::memo()->tags(['foo'])->getMultiple(['missing'], fn () => 'fallback'),
        );

        $this->assertSame(
            ['missing' => null],
            Cache::memo()->tags(['foo'])->get(['missing']),
        );
    }

    public function test_it_supports_enum_keys()
    {
        Cache::tags(['foo'])->put(MemoizedTaggedCacheTestKey::Name, 'Tim', 60);

        $this->assertSame('Tim', Cache::memo()->tags(['foo'])->get(MemoizedTaggedCacheTestKey::Name));
    }

    public function test_mutations_forget_memoized_values()
    {
        Cache::tags(['foo'])->put('name', 'Tim', 60);
        Cache::memo()->tags(['foo'])->get('name');

        Cache::memo()->tags(['foo'])->forever('name', 'Taylor');
        $this->assertSame('Taylor', Cache::memo()->tags(['foo'])->get('name'));

        Cache::tags(['foo'])->put('name', 'Abigail', 60);
        Cache::memo()->tags(['foo'])->touch('name', 60);
        $this->assertSame('Abigail', Cache::memo()->tags(['foo'])->get('name'));

        Cache::tags(['foo'])->put('name', 'Nuno', 60);
        Cache::memo()->tags(['foo'])->get('name');
        Cache::tags(['foo'])->put('name', 'Jess', 60);
        $this->assertFalse(Cache::memo()->tags(['foo'])->add('name', 'Adam', 60));
        $this->assertSame('Jess', Cache::memo()->tags(['foo'])->get('name'));
    }

    public function test_clear_forgets_all_memoized_values()
    {
        Cache::put('untagged', 'Tim', 60);
        Cache::tags(['foo'])->put('foo', 'Taylor', 60);
        Cache::tags(['bar'])->put('bar', 'Jess', 60);

        Cache::memo()->get('untagged');
        Cache::memo()->tags(['foo'])->get('foo');
        Cache::memo()->tags(['bar'])->get('bar');

        Cache::memo()->tags(['foo'])->clear();

        $this->assertNull(Cache::memo()->get('untagged'));
        $this->assertNull(Cache::memo()->tags(['foo'])->get('foo'));
        $this->assertNull(Cache::memo()->tags(['bar'])->get('bar'));
    }

    public function test_it_dispatches_decorated_driver_events_only()
    {
        $events = [];

        Event::listen('*', function ($type, $event) use (&$events) {
            if ($event[0] instanceof CacheEvent) {
                $events[] = $event[0];
            }
        });

        Cache::memo()->tags(['foo'])->get('name');

        $this->assertCount(2, $events);
        $this->assertInstanceOf(RetrievingKey::class, $events[0]);
        $this->assertInstanceOf(CacheMissed::class, $events[1]);
    }
}

enum MemoizedTaggedCacheTestKey
{
    case Name;
}
