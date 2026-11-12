<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\RedisStore;
use Illuminate\Cache\RedisTaggedCache;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Redis\Connections\PredisClusterConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Predis\Pipeline\Pipeline;

class CacheRedisTaggedCacheReadTest extends TestCase
{
    public function testManyReadsTaggedHitsAndMissesInOnePipeline()
    {
        $client = $this->createMock(Client::class);
        $redis = new PredisConnection($client);
        $factory = $this->createStub(Factory::class);
        $factory->method('connection')->willReturn($redis);
        $cache = (new RedisStore($factory, 'prefix:'))->tags(['store', 'facets']);

        $this->expectPipeline($client, [
            'prefix:'.$cache->taggedItemKey('hit'),
            'prefix:'.$cache->taggedItemKey('123'),
            'prefix:'.$cache->taggedItemKey('miss'),
        ], [serialize(['facet']), null, null]);

        $this->assertSame([
            'hit' => ['facet'],
            123 => null,
            'miss' => 'fallback',
        ], $cache->many(['hit', '123', 'miss' => fn () => 'fallback']));
    }

    public function testManyUsesIndividualReadsWithPredisCluster()
    {
        $redis = $this->getMockBuilder(PredisClusterConnection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['command'])
            ->getMock();
        $factory = $this->createStub(Factory::class);
        $factory->method('connection')->willReturn($redis);
        $cache = (new RedisStore($factory, 'prefix:'))->tags(['store']);

        $redis->expects($this->exactly(2))->method('command')->willReturnMap([
            ['get', ['prefix:'.$cache->taggedItemKey('hit')], serialize('cached')],
            ['get', ['prefix:'.$cache->taggedItemKey('miss')], null],
        ]);

        $this->assertSame(['hit' => 'cached', 'miss' => 'fallback'], $cache->many(['hit', 'miss' => 'fallback']));
    }

    public function testFlexibleReadsFreshTaggedValueAndTimestampInOnePipeline()
    {
        $client = $this->createMock(Client::class);
        $redis = new PredisConnection($client);
        $factory = $this->createStub(Factory::class);
        $factory->method('connection')->willReturn($redis);
        $cache = (new RedisStore($factory, 'prefix:'))->tags(['store']);

        $this->expectPipeline($client, [
            'prefix:'.$cache->taggedItemKey('key'),
            'prefix:'.$cache->taggedItemKey(RedisTaggedCache::FLEXIBLE_CREATED_KEY_PREFIX.'key'),
        ], [serialize('cached'), Carbon::now()->getTimestamp()]);

        $this->assertSame('cached', $cache->flexible('key', [60, 120], fn () => $this->fail('The cached value is fresh.')));
    }

    private function expectPipeline(Client $client, array $keys, array $results): void
    {
        $client->expects($this->once())->method('pipeline')->willReturnCallback(function ($callback) use ($keys, $results) {
            $pipeline = $this->getMockBuilder(Pipeline::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['__call'])
                ->getMock();

            $index = 0;
            $pipeline->expects($this->exactly(count($keys)))->method('__call')
                ->willReturnCallback(function ($command, $arguments) use ($keys, &$index) {
                    $this->assertSame('get', $command);
                    $this->assertSame([$keys[$index++]], $arguments);
                });

            $callback($pipeline);

            return $results;
        });
    }
}
