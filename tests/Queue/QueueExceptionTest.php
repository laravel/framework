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
