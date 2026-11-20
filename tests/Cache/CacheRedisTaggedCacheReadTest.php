<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\RedisStore;
use Illuminate\Cache\RedisTaggedCache;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisClusterConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Predis\Pipeline\Pipeline;
use Redis;
use RedisException;

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

    #[RequiresPhpExtension('redis')]
    public function testManyRetriesPhpRedisReadsWhenThePipelineLosesItsConnection()
    {
        $failedClient = $this->createMock(Redis::class);
        $failedClient->expects($this->once())->method('pipeline')->willReturnSelf();
        $failedClient->expects($this->exactly(2))->method('get')->willReturnSelf();
        $failedClient->expects($this->once())->method('exec')
            ->willThrowException(new RedisException('Connection closed'));
        $failedClient->expects($this->once())->method('discard');
        $failedClient->expects($this->once())->method('close')->willReturn(true);

        $healthyClient = $this->createMock(Redis::class);
        $healthyClient->expects($this->once())->method('pipeline')->willReturnSelf();
        $healthyClient->expects($this->exactly(2))->method('get')->willReturnSelf();
        $healthyClient->expects($this->once())->method('exec')->willReturn([serialize('cached'), false]);
        $healthyClient->expects($this->once())->method('getOption')->with(Redis::OPT_SERIALIZER)->willReturn(Redis::SERIALIZER_NONE);

        $redis = new PhpRedisConnection($failedClient, fn () => $healthyClient);
        $factory = $this->createStub(Factory::class);
        $factory->method('connection')->willReturn($redis);
        $cache = (new RedisStore($factory, 'prefix:'))->tags(['store']);

        $this->assertSame(['hit' => 'cached', 'miss' => null], $cache->many(['hit', 'miss']));
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
