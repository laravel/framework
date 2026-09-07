<?php

namespace Illuminate\Tests\Cache;

use DateInterval;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\RedisTaggedCache;
use Illuminate\Cache\RedisTagSet;
use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class CacheRedisTaggedCacheTest extends TestCase
{
    #[DataProvider('successfulWrites')]
    public function testTagEntriesAreRegisteredAfterSuccessfulWrites(string $method, array $arguments, string $storeMethod, array $storeArguments, array $tagArguments, mixed $result)
    {
        [$cache, $store, $tags, $itemKey] = $this->getCache();

        $store->expects($storeMethod)
            ->with($itemKey, ...$storeArguments)
            ->once()
            ->globally()->ordered()
            ->andReturn($result);

        $tags->expects('addEntry')
            ->with($itemKey, ...$tagArguments)
            ->once()
            ->globally()->ordered();

        $this->assertSame($result, $cache->{$method}('key', ...$arguments));
    }

    public static function successfulWrites(): array
    {
        return [
            'put' => ['put', ['value', 60], 'put', ['value', 60], [60], true],
            'touch' => ['touch', [60], 'touch', [60], [60], true],
            'forever' => ['forever', ['value'], 'forever', ['value'], [], true],
            'add' => ['add', ['value', 60], 'add', ['value', 60], [60], true],
            'increment' => ['increment', [2], 'increment', [2], [null, 'NX'], 2],
            'decrement' => ['decrement', [2], 'decrement', [2], [null, 'NX'], -2],
        ];
    }

    #[DataProvider('failedWrites')]
    public function testFailedWritesDoNotRegisterTagEntries(string $method, array $arguments, string $storeMethod, array $storeArguments)
    {
        [$cache, $store, $tags, $itemKey] = $this->getCache();

        $store->expects($storeMethod)
            ->with($itemKey, ...$storeArguments)
            ->once()
            ->andReturn(false);

        $tags->expects('addEntry')->never();

        $this->assertFalse($cache->{$method}('key', ...$arguments));
    }

    public static function failedWrites(): array
    {
        return [
            'put' => ['put', ['value', 60], 'put', ['value', 60]],
            'touch' => ['touch', [60], 'touch', [60]],
            'forever' => ['forever', ['value'], 'forever', ['value']],
            'add' => ['add', ['value', 60], 'add', ['value', 60]],
            'increment' => ['increment', [2], 'increment', [2]],
            'decrement' => ['decrement', [2], 'decrement', [2]],
        ];
    }

    #[TestWith([0])]
    #[TestWith([-1])]
    #[TestWith(['past'])]
    public function testTouchWithExpiredTtlForgetsItem(int|string $ttl)
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 1, 0, 0, 0));

        try {
            [$cache, $store, $tags, $itemKey] = $this->getCache();

            $store->expects('forget')->with($itemKey)->once()->andReturn(true);
            $store->expects('touch')->never();
            $tags->expects('addEntry')->never();

            $this->assertTrue($cache->touch('key', $ttl === 'past' ? Carbon::now()->subSecond() : $ttl));
        } finally {
            Carbon::setTestNow();
        }
    }

    #[TestWith(['interval'])]
    #[TestWith(['datetime'])]
    public function testTouchRegistersTagEntriesWithTheSameSecondsAsTheStore(string $type)
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 1, 0, 0, 0));

        try {
            [$cache, $store, $tags, $itemKey] = $this->getCache();
            $ttl = $type === 'interval' ? new DateInterval('PT120S') : Carbon::now()->addSeconds(120);

            $store->expects('touch')->with($itemKey, 120)->once()->globally()->ordered()->andReturn(true);
            $tags->expects('addEntry')->with($itemKey, 120)->once()->globally()->ordered();

            $this->assertTrue($cache->touch('key', $ttl));
        } finally {
            Carbon::setTestNow();
        }
    }

    private function getCache(): array
    {
        $store = Mockery::mock(RedisStore::class);
        $tags = Mockery::mock(RedisTagSet::class);
        $tags->allows('getNamespace')->andReturn('namespace');
        $tags->allows('getNames')->andReturn([]);

        return [
            new RedisTaggedCache($store, $tags),
            $store,
            $tags,
            sha1('namespace').':key',
        ];
    }
}
