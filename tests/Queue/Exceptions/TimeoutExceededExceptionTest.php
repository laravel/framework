<?php

namespace Illuminate\Tests\Queue\Exceptions;

use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\TimeoutExceededException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class TimeoutExceededExceptionTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testExceptionIsInstanceOfMaxAttemptsExceededException()
    {
        $job = m::mock(SyncJob::class);
        $job->shouldReceive('resolveName')->andReturn('App\\Jobs\\UnderlyingJob');

        $exception = TimeoutExceededException::forJob($job);

        $this->assertInstanceOf(MaxAttemptsExceededException::class, $exception);
    }

    public function testForJobBuildsExceptionAndAssignsJob()
    {
        $job = m::mock(SyncJob::class);
        $job->shouldReceive('resolveName')->andReturn('App\\Jobs\\UnderlyingJob');

        $exception = TimeoutExceededException::forJob($job);

        $this->assertSame($job, $exception->job);
        $this->assertSame('App\\Jobs\\UnderlyingJob has timed out.', $exception->getMessage());
    }
}
