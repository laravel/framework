<?php

namespace Illuminate\Tests\Integration\Cache;

use Carbon\CarbonInterval;
use Illuminate\Cache\RedisTaggedCache;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;

class RedisStoreTest extends TestCase
{
    use InteractsWithRedis;

    private Repository $redisRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();

        $this->redisRepository = Cache::store('redis');
        $this->redisRepository->clear();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownRedis();
    }

    public function testItCanStoreInfinite()
    {
        $result = $this->redisRepository->put('foo', INF);
        $this->assertTrue($result);
        $this->assertSame(INF, $this->redisRepository->get('foo'));

        $result = $this->redisRepository->put('bar', -INF);
        $this->assertTrue($result);
        $this->assertSame(-INF, $this->redisRepository->get('bar'));
    }

    public function testItCanStoreNan()
    {
        $result = $this->redisRepository->put('foo', NAN);
        $this->assertTrue($result);
        $this->assertNan($this->redisRepository->get('foo'));
    }

    public function testItFlushesStaleTags()
    {
        $this->redisRepository->put('forever-key', 'value1');
        $this->redisRepository->tags('one')->put('one-forever-key', 'value2');
        $this->redisRepository->tags('one')->put('one-expired-key', 'value3', CarbonInterval::second());
        $this->redisRepository->tags('one', 'two')->put('one-two-valid-key', 'value4', CarbonInterval::hour());
        $this->redisRepository->tags('three')->put('three-expired-key', 'value5', CarbonInterval::second());

        $tagOneReferenceKey = $this->redisRepository->tags('one')->getTags()->getNamespace();
        $tagTwoReferenceKey = $this->redisRepository->tags('two')->getTags()->getNamespace();
        $oneForeverValueKey = $this->redisRepository->tags('one')->taggedItemKey('one-forever-key');
        $oneTwoValidValueKey = $this->redisRepository->tags('one', 'two')->taggedItemKey('one-two-valid-key');

        sleep(2);

        $this->redisRepository->flushStale();

        $this->assertEqualsCanonicalizing([
            'laravel_database_laravel_cache_:forever-key',
            "laravel_database_laravel_cache_:$oneForeverValueKey",
            "laravel_database_laravel_cache_:$oneTwoValidValueKey",
            'laravel_database_laravel_cache_:tag:one:key',
            'laravel_database_laravel_cache_:tag:two:key',
            "laravel_database_laravel_cache_:$tagOneReferenceKey:" . RedisTaggedCache::REFERENCE_KEY_FOREVER,
            "laravel_database_laravel_cache_:$tagOneReferenceKey:" . RedisTaggedCache::REFERENCE_KEY_STANDARD,
            "laravel_database_laravel_cache_:$tagTwoReferenceKey:" . RedisTaggedCache::REFERENCE_KEY_STANDARD,
        ], $this->redisRepository->connection()->keys('*'));

        $this->assertEqualsCanonicalizing([
            "laravel_cache_:$oneForeverValueKey",
        ], $this->redisRepository->connection()->smembers("laravel_cache_:$tagOneReferenceKey:" . RedisTaggedCache::REFERENCE_KEY_FOREVER));
        $this->assertEqualsCanonicalizing([
            "laravel_cache_:$oneTwoValidValueKey",
        ], $this->redisRepository->connection()->smembers("laravel_cache_:$tagOneReferenceKey:" . RedisTaggedCache::REFERENCE_KEY_STANDARD));
        $this->assertEqualsCanonicalizing([
            "laravel_cache_:$oneTwoValidValueKey",
        ], $this->redisRepository->connection()->smembers("laravel_cache_:$tagTwoReferenceKey:" . RedisTaggedCache::REFERENCE_KEY_STANDARD));

        $this->assertSame('value1', $this->redisRepository->get('forever-key'));
        $this->assertSame('value2', $this->redisRepository->tags(['one'])->get('one-forever-key'));
        $this->assertNull($this->redisRepository->tags(['one'])->get('one-expired-key'));
        $this->assertSame('value4', $this->redisRepository->tags(['one', 'two'])->get('one-two-valid-key'));
        $this->assertNull($this->redisRepository->tags(['three'])->get('three-expired-key'));
    }
}
