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
        $cache = Cache::store('redis');
        $connection = $cache->getStore()->connection();

        if (! $connection instanceof PhpRedisConnection) {
            $this->markTestSkipped('Serialization can only be enabled on the phpredis client.');
        }

        $connection->client()->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

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
