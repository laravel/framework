<?php

namespace Illuminate\Tests\Queue\Exceptions;

use Exception;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Queue\MaxAttemptsExceededException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MaxAttemptsExceededExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = new MaxAttemptsExceededException;

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new MaxAttemptsExceededException('Attempted too many times.', 42, $previous);

        $this->assertSame('Attempted too many times.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testJobDefaultsToNull(): void
    {
        $exception = new MaxAttemptsExceededException;

        $this->assertNull($exception->job);
    }

    public function testForJobBuildsExceptionAndAssignsJob(): void
    {
        $job = m::mock(SyncJob::class);
        $job->shouldReceive('resolveName')->andReturn('App\\Jobs\\UnderlyingJob');

        $exception = MaxAttemptsExceededException::forJob($job);

        $this->assertSame($job, $exception->job);
        $this->assertSame('App\\Jobs\\UnderlyingJob has been attempted too many times.', $exception->getMessage());
    }
}
