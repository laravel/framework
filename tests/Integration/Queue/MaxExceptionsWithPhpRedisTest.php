<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Redis;
use RuntimeException;

#[RequiresPhpExtension('redis')]
class MaxExceptionsWithPhpRedisTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();

        $this->markTestSkippedUnless(
            $this->app['redis']->connection() instanceof PhpRedisConnection,
            'This test is for phpredis only',
        );
    }

    protected function tearDown(): void
    {
        $this->tearDownRedis();

        parent::tearDown();
    }

    #[DataProvider('phpRedisOptionsProvider')]
    public function testMaxExceptionsAreCountedWithPhpRedisSerializationOrCompression($serializer, $compression)
    {
        foreach ([$serializer, $compression] as $option) {
            if (! defined('Redis::'.$option)) {
                $this->markTestSkipped("Redis::{$option} is not available.");
            }
        }

        $this->app['config']->set('cache.stores.redis.connection', 'default');

        $cache = Cache::store('redis');

        $client = $cache->getStore()->connection()->client();
        $client->setOption(Redis::OPT_SERIALIZER, constant('Redis::'.$serializer));
        $client->setOption(Redis::OPT_COMPRESSION, constant('Redis::'.$compression));

        $worker = $this->app->make('queue.worker');
        $worker->setCache($cache);

        $payload = json_encode([
            'uuid' => (string) Str::uuid(),
            'job' => MaxExceptionsWithPhpRedisTestHandler::class,
            'data' => [],
            'maxTries' => 10,
            'maxExceptions' => 2,
        ]);

        $this->assertFalse($this->processFailingJob($worker, $payload)->hasFailed());
        $this->assertTrue($this->processFailingJob($worker, $payload)->hasFailed());
    }

    public static function phpRedisOptionsProvider()
    {
        return [
            'no serialization or compression' => ['SERIALIZER_NONE', 'COMPRESSION_NONE'],
            'php serializer' => ['SERIALIZER_PHP', 'COMPRESSION_NONE'],
            'igbinary serializer' => ['SERIALIZER_IGBINARY', 'COMPRESSION_NONE'],
            'msgpack serializer' => ['SERIALIZER_MSGPACK', 'COMPRESSION_NONE'],
            'lzf compression' => ['SERIALIZER_NONE', 'COMPRESSION_LZF'],
            'zstd compression' => ['SERIALIZER_NONE', 'COMPRESSION_ZSTD'],
            'lz4 compression' => ['SERIALIZER_NONE', 'COMPRESSION_LZ4'],
            'igbinary serializer and zstd compression' => ['SERIALIZER_IGBINARY', 'COMPRESSION_ZSTD'],
        ];
    }

    protected function processFailingJob($worker, $payload)
    {
        $job = new SyncJob($this->app, $payload, 'redis', 'default');

        try {
            $worker->process('redis', $job, new WorkerOptions);
        } catch (RuntimeException) {
            //
        }

        return $job;
    }
}

class MaxExceptionsWithPhpRedisTestHandler
{
    public function fire($job, $data)
    {
        throw new RuntimeException('Something went wrong.');
    }
}
