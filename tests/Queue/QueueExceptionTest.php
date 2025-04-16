<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\TimeoutExceededException;
use PHPUnit\Framework\TestCase;

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

    public function test_timeout_exception_can_be_converted_to_string()
    {
        $e = TimeoutExceededException::forJob($job = new MyFakeRedisJob());

        $this->assertIsString((string) $e);
        $this->assertStringContainsString('timed out', (string) $e);
    }

    public function test_max_attempts_exception_can_be_converted_to_string()
    {
        $e = MaxAttemptsExceededException::forJob($job = new MyFakeRedisJob());

        $this->assertIsString((string) $e);
        $this->assertStringContainsString('attempted too many times', (string) $e);
    }

    public function test_exceptions_preserve_job_instance()
    {
        $job = new MyFakeRedisJob();

        $timeoutException = TimeoutExceededException::forJob($job);
        $maxAttemptsException = MaxAttemptsExceededException::forJob($job);

        $this->assertSame($job, $timeoutException->job);
        $this->assertSame($job, $maxAttemptsException->job);
        $this->assertInstanceOf(RedisJob::class, $timeoutException->job);
        $this->assertInstanceOf(RedisJob::class, $maxAttemptsException->job);
    }

    public function test_custom_job_name_resolution()
    {
        $job = new class extends MyFakeRedisJob
        {
            public function resolveName()
            {
                return 'Custom\\Job\\Name';
            }
        };

        $e = TimeoutExceededException::forJob($job);

        $this->assertStringContainsString('Custom\\Job\\Name', $e->getMessage());
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
