<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Orchestra\Testbench\Attributes\RequiresEnv;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Redis;
use RuntimeException;

#[RequiresEnv('REDIS_CLIENT')]
#[RequiresPhpExtension('redis')]
class MaxExceptionsWithRedisTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        $this->tearDownRedis();

        parent::tearDown();
    }

    public function testMaxExceptionsAreEnforcedWhenPhpRedisSerializationIsEnabled()
    {
        $this->runMaxExceptionsScenario(Redis::SERIALIZER_PHP);

        if (defined('Redis::SERIALIZER_IGBINARY')) {
            $this->runMaxExceptionsScenario(Redis::SERIALIZER_IGBINARY);
        }
    }

    public function testMaxExceptionsAreEnforcedWhenPhpRedisCompressionIsEnabled()
    {
        if (! defined('Redis::COMPRESSION_LZF')) {
            $this->markTestSkipped('Compression is not available in this phpredis build.');
        }

        $this->runMaxExceptionsScenario(Redis::SERIALIZER_NONE, Redis::COMPRESSION_LZF);
    }

    /**
     * Run a job that always throws with maxExceptions = 2 twice and assert
     * that the second attempt fails the job, even when the phpredis client
     * serializes or compresses the cached counter value.
     */
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
                'job' => AlwaysFailingTestHandler::class,
                'data' => [],
                'maxTries' => 10,
                'maxExceptions' => 2,
            ]);

            $firstAttempt = $this->processFailingJob($worker, $payload);
            $secondAttempt = $this->processFailingJob($worker, $payload);

            $this->assertFalse($firstAttempt->hasFailed());
            $this->assertTrue($secondAttempt->hasFailed());
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

class AlwaysFailingTestHandler
{
    public function fire($job, $data)
    {
        throw new RuntimeException('Something went wrong.');
    }
}
