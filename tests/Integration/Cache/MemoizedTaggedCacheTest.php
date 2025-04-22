<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
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
        Redis::flushAll();
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
}