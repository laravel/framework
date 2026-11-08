<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use RuntimeException;

#[RequiresPhpExtension('redis')]
class WorkerMaxExceptionsRedisTest extends TestCase
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

    public function testMaxExceptionsWithIgbinarySerialization(): void
    {
        if (! defined('Redis::SERIALIZER_IGBINARY')) {
            $this->markTestSkipped('Igbinary is not available.');
        }

        $this->app->instance('redis', $this->redis['phpredis']);
        config([
            'cache.stores.redis.connection' => 'default',
        ]);

        $cache = Cache::store('redis');
        $connection = $cache->getStore()->connection();

        $this->assertInstanceOf(PhpRedisConnection::class, $connection);

        $client = $connection->client();

        $oldSerializer = $client->getOption(\Redis::OPT_SERIALIZER);
        $key = 'job-exceptions:issue-62324';

        try {
            $client->setOption(
                \Redis::OPT_SERIALIZER,
                \Redis::SERIALIZER_IGBINARY
            );

            $worker = new Worker(
                $this->app['queue'],
                $this->app['events'],
                $this->app->make(ExceptionHandler::class),
                fn () => false
            );

            $worker->setCache($cache);

            $job = $this->createMock(Job::class);

            $job->method('uuid')->willReturn('issue-62324');
            $job->method('maxExceptions')->willReturn(2);

            $job->expects($this->once())
                ->method('fail')
                ->with($this->isInstanceOf(RuntimeException::class));

            $job->expects($this->exactly(2))
                ->method('fire')
                ->willThrowException(new RuntimeException('Job failed'));

            for ($attempt = 0; $attempt < 2; $attempt++) {
                try {
                    $worker->process(
                        'redis',
                        $job,
                        new WorkerOptions
                    );
                } catch (RuntimeException) {
                    // Expected exception from the job.
                }
            }

            $this->assertSame(
                \Redis::SERIALIZER_IGBINARY,
                $client->getOption(\Redis::OPT_SERIALIZER)
            );
        } finally {
            $cache->forget($key);

            $client->setOption(
                \Redis::OPT_SERIALIZER,
                $oldSerializer
            );
        }
    }
}
