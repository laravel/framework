<?php

namespace Illuminate\Tests\Queue;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\Queue;
use Illuminate\Queue\TimeoutExceededException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class QueueExceptionTest extends TestCase
{
    public function test_it_can_create_timeout_exception_for_job()
    {
        $e = TimeoutExceededException::forJob($job = new MyFakeRedisJob());

        $this->assertSame('App\\Jobs\\UnderlyingJob has timed out.', $e->getMessage());
        $this->assertSame($job, $e->job);
    }

    public function test_it_can_create_max_attempts_exception_for_job()
    {
        $e = MaxAttemptsExceededException::forJob($job = new MyFakeRedisJob());

        $this->assertSame('App\\Jobs\\UnderlyingJob has been attempted too many times.', $e->getMessage());
        $this->assertSame($job, $e->job);
    }

    public function test_it_throws_runtime_exception_with_class_name_on_serialization_failure()
    {
        $job = new FakeJob;

        $container = new Container;

        $queue = new class($container) extends Queue
        {
            public function __construct($container)
            {
                $this->container = $container;
            }

            public function publicCreateObjectPayload($job, $queue)
            {
                return $this->createObjectPayload($job, $queue);
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(get_class($job));

        $queue->publicCreateObjectPayload($job, 'default');
    }
}

class MyFakeRedisJob extends RedisJob
{
    public function __construct()
    {
        //
    }

    public function resolveName()
    {
        return 'App\\Jobs\\UnderlyingJob';
    }
}

class FakeJob
{
    public Closure $closure;

    public function __construct()
    {
        $this->closure = function () {};
    }
}
