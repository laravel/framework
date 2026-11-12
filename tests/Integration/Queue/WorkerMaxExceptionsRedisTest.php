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
        parent::setUp();

        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        $this->tearDownRedis();

        parent::tearDown();
    }

    public function testMaxExceptionsIsHonouredWhenPhpRedisSerializesValues()
    {
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
            $client->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);

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
                    $worker->process('sync', $job, new WorkerOptions);
                } catch (RuntimeException) {
                    //
                }
            }

            $this->assertSame(\Redis::SERIALIZER_PHP, $client->getOption(\Redis::OPT_SERIALIZER));
        } finally {
            $cache->forget($key);
            $client->setOption(\Redis::OPT_SERIALIZER, $oldSerializer);
        }
    }
}
