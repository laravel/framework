<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Redis;
use RuntimeException;

#[RequiresPhpExtension('redis')]
class MaxExceptionsWithRedisTest extends TestCase
{
    use InteractsWithRedis;

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

    public function testMaxExceptionsAreCountedWhenPhpRedisSerializationIsEnabled()
    {
        $this->runMaxExceptionsScenario(Redis::SERIALIZER_PHP);

        if (defined('Redis::SERIALIZER_IGBINARY')) {
            $this->runMaxExceptionsScenario(Redis::SERIALIZER_IGBINARY);
        }
    }

    public function testMaxExceptionsAreCountedWhenPhpRedisCompressionIsEnabled()
    {
        if (! defined('Redis::COMPRESSION_LZF')) {
            $this->markTestSkipped('Compression is not available in this phpredis build.');
        }

        $this->runMaxExceptionsScenario(Redis::SERIALIZER_PHP, Redis::COMPRESSION_LZF);
    }

    protected function runMaxExceptionsScenario(int $serializer, ?int $compression = null): void
    {
        $this->app['config']->set('cache.stores.redis.connection', 'default');

        $cache = Cache::store('redis');
        $connection = $cache->getStore()->connection();

        if (! $connection instanceof PhpRedisConnection) {
            $this->markTestSkipped('Serialization can only be enabled on the phpredis client.');
        }

        $client = $connection->client();
        $oldSerializer = $client->getOption(Redis::OPT_SERIALIZER);
        $oldCompression = defined('Redis::OPT_COMPRESSION')
            ? $client->getOption(Redis::OPT_COMPRESSION)
            : null;

        try {
            $client->setOption(Redis::OPT_SERIALIZER, $serializer);

            if ($compression !== null) {
                $client->setOption(Redis::OPT_COMPRESSION, $compression);
            }

            $worker = $this->app->make('queue.worker');
            $worker->setCache($cache);

            $payload = json_encode([
                'uuid' => (string) Str::uuid(),
                'job' => MaxExceptionsWithRedisTestHandler::class,
                'data' => [],
                'maxTries' => 10,
                'maxExceptions' => 2,
            ]);

            $this->assertFalse($this->processFailingJob($worker, $payload)->hasFailed());
            $this->assertTrue($this->processFailingJob($worker, $payload)->hasFailed());
        } finally {
            $client->setOption(Redis::OPT_SERIALIZER, $oldSerializer);

            if ($oldCompression !== null) {
                $client->setOption(Redis::OPT_COMPRESSION, $oldCompression);
            }
        }
    }

    protected function processFailingJob(Worker $worker, string $payload): SyncJob
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

class MaxExceptionsWithRedisTestHandler
{
    public function fire($job, $data)
    {
        throw new RuntimeException('Something went wrong.');
    }
}
