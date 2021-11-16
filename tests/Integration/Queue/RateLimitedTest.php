<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class RateLimitedTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function testUnlimitedJobsAreExecuted()
    {
        $rateLimiter = $this->app->make(RateLimiter::class);

        $rateLimiter->for('test', function ($job) {
            return Limit::none();
        });

        $this->assertJobRanSuccessfully(RateLimitedTestJob::class);
        $this->assertJobRanSuccessfully(RateLimitedTestJob::class);
    }

    public function testRateLimitedJobsAreNotExecutedOnLimitReached2()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('get')->andReturn(0, 1, null);
        $cache->shouldReceive('add')->andReturn(true, true);
        $cache->shouldReceive('increment')->andReturn(1);
        $cache->shouldReceive('has')->andReturn(true);

        $rateLimiter = new RateLimiter($cache);
        $this->app->instance(RateLimiter::class, $rateLimiter);
        $rateLimiter = $this->app->make(RateLimiter::class);

        $rateLimiter->for('test', function ($job) {
            return Limit::perHour(1);
        });

        $this->assertJobRanSuccessfully(RateLimitedTestJob::class);

        // Assert Job was released and released with a delay greater than 0
        RateLimitedTestJob::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('release')->once()->withArgs(function ($delay) {
            return $delay >= 0;
        });
        $job->shouldReceive('isReleased')->andReturn(true);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(true);

        $instance->call($job, [
            'command' => serialize($command = new RateLimitedTestJob),
        ]);

        $this->assertFalse(RateLimitedTestJob::$handled);
    }

    public function testRateLimitedJobsAreNotExecutedOnLimitReached()
    {
        $rateLimiter = $this->app->make(RateLimiter::class);

        $rateLimiter->for('test', function ($job) {
            return Limit::perHour(1);
        });

        $this->assertJobRanSuccessfully(RateLimitedTestJob::class);
        $this->assertJobWasReleased(RateLimitedTestJob::class);
    }

    public function testRateLimitedJobsCanBeSkippedOnLimitReached()
    {
        $rateLimiter = $this->app->make(RateLimiter::class);

        $rateLimiter->for('test', function ($job) {
            return Limit::perHour(1);
        });

        $this->assertJobRanSuccessfully(RateLimitedDontReleaseTestJob::class);
        $this->assertJobWasSkipped(RateLimitedDontReleaseTestJob::class);
    }

    public function testJobsCanHaveConditionalRateLimits()
    {
        $rateLimiter = $this->app->make(RateLimiter::class);

        $rateLimiter->for('test', function ($job) {
            if ($job->isAdmin()) {
                return Limit::none();
            }

            return Limit::perHour(1);
        });

        $this->assertJobRanSuccessfully(AdminTestJob::class);
        $this->assertJobRanSuccessfully(AdminTestJob::class);

        $this->assertJobRanSuccessfully(NonAdminTestJob::class);
        $this->assertJobWasReleased(NonAdminTestJob::class);
    }

    public function testMiddlewareSerialization()
    {
        $rateLimited = new RateLimited('limiterName');
        $rateLimited->shouldRelease = false;

        $restoredRateLimited = unserialize(serialize($rateLimited));

        $fetch = (function (string $name) {
            return $this->{$name};
        })->bindTo($restoredRateLimited, RateLimited::class);

        $this->assertFalse($restoredRateLimited->shouldRelease);
        $this->assertSame('limiterName', $fetch('limiterName'));
        $this->assertInstanceOf(RateLimiter::class, $fetch('limiter'));
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

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertTrue($class::$handled);
    }

    protected function assertJobWasReleased($class)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('release')->once();
        $job->shouldReceive('isReleased')->andReturn(true);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(true);

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertFalse($class::$handled);
    }

    protected function assertJobWasSkipped($class)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(false);
        $job->shouldReceive('delete')->once();

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertFalse($class::$handled);
    }
}

class RateLimitedTestJob
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }

    public function middleware()
    {
        return [new RateLimited('test')];
    }
}

class AdminTestJob extends RateLimitedTestJob
{
    public function isAdmin()
    {
        return true;
    }
}

class NonAdminTestJob extends RateLimitedTestJob
{
    public function isAdmin()
    {
        return false;
    }
}

class RateLimitedDontReleaseTestJob extends RateLimitedTestJob
{
    public function middleware()
    {
        return [(new RateLimited('test'))->dontRelease()];
    }
}
