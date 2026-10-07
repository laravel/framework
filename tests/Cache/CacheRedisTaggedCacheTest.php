<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\RedisStore;
use Illuminate\Cache\RedisTaggedCache;
use Illuminate\Cache\RedisTagSet;
use JMac\Testing\Double;
use PHPUnit\Framework\Attributes\DataProvider;
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
            'forever' => ['forever', ['value'], 'forever', ['value'], [], true],
            'add' => ['add', ['value', 60], 'add', ['value', 60], [60], true],
            'increment' => ['increment', [2], 'increment', [2], [null, 'NX'], 2],
            'decrement' => ['decrement', [2], 'decrement', [2], [null, 'NX'], -2],
            'touch' => ['touch', [60], 'touch', [60], [60], true],
        ];
    }

    #[DataProvider('failedWrites')]
    public function testFailedWritesDoNotRegisterTagEntries(string $method, array $arguments, string $storeMethod, array $storeArguments)
    {
        [$cache, $store, $tags, $itemKey] = $this->getCache();

        $store->expects($storeMethod)->with($itemKey, ...$storeArguments)->returns(false);

        $tags->expects('addEntry')->never();

        $this->assertFalse($cache->{$method}('key', ...$arguments));
    }

    public static function failedWrites(): array
    {
        return [
            'put' => ['put', ['value', 60], 'put', ['value', 60]],
            'forever' => ['forever', ['value'], 'forever', ['value']],
            'add' => ['add', ['value', 60], 'add', ['value', 60]],
            'increment' => ['increment', [2], 'increment', [2]],
            'decrement' => ['decrement', [2], 'decrement', [2]],
            'touch' => ['touch', [60], 'touch', [60]],
        ];
    }

    private function getCache(): array
    {
        $store = Double::for(RedisStore::class);
        $tags = Double::for(RedisTagSet::class);
        $tags->allows('getNamespace')->returns('namespace');
        $tags->allows('getNames')->returns([]);

        return [
            new RedisTaggedCache($store, $tags),
            $store,
            $tags,
            sha1('namespace').':key',
        ];
    }
}
