<?php

namespace Illuminate\Tests\Integration\Queue;

use Exception;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class ThrottlesExceptionsTest extends TestCase
{
    public function testCircuitIsOpenedForJobErrors()
    {
        $this->assertJobWasReleasedImmediately(CircuitBreakerTestJob::class);
        $this->assertJobWasReleasedImmediately(CircuitBreakerTestJob::class);
        $this->assertJobWasReleasedWithDelay(CircuitBreakerTestJob::class);
    }

    public function testCircuitStaysClosedForSuccessfulJobs()
    {
        $this->assertJobRanSuccessfully(CircuitBreakerSuccessfulJob::class);
        $this->assertJobRanSuccessfully(CircuitBreakerSuccessfulJob::class);
        $this->assertJobRanSuccessfully(CircuitBreakerSuccessfulJob::class);
    }

    public function testCircuitResetsAfterSuccess()
    {
        $this->assertJobWasReleasedImmediately(CircuitBreakerTestJob::class);
        $this->assertJobRanSuccessfully(CircuitBreakerSuccessfulJob::class);
        $this->assertJobWasReleasedImmediately(CircuitBreakerTestJob::class);
        $this->assertJobWasReleasedImmediately(CircuitBreakerTestJob::class);
        $this->assertJobWasReleasedWithDelay(CircuitBreakerTestJob::class);
    }

    protected function assertJobWasReleasedImmediately($class)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('release')->with(0)->once();
        $job->shouldReceive('isReleased')->andReturn(true);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(true);
        $job->shouldReceive('uuid')->andReturn('simple-test-uuid');

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertTrue($class::$handled);
    }

    protected function assertJobWasReleasedWithDelay($class)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('release')->withArgs(function ($delay) {
            return $delay >= 600;
        })->once();
        $job->shouldReceive('isReleased')->andReturn(true);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(true);
        $job->shouldReceive('uuid')->andReturn('simple-test-uuid');

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertFalse($class::$handled);
    }

    protected function assertJobRanSuccessfully($class)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(false);
        $job->shouldReceive('delete')->once();
        $job->shouldReceive('uuid')->andReturn('simple-test-uuid');

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertTrue($class::$handled);
    }
}

class CircuitBreakerTestJob
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;

        throw new Exception;
    }

    public function middleware()
    {
        return [(new ThrottlesExceptions(2, 10))->by('test')];
    }
}

class CircuitBreakerSuccessfulJob
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }

    public function middleware()
    {
        return [(new ThrottlesExceptions(2, 10))->by('test')];
    }
}
