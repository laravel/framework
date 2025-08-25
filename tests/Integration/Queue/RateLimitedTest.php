<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Carbon;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class RateLimitedTest extends TestCase
{
    public function testUnlimitedJobsAreExecuted()
    {
        $rateLimiter = $this->app->make(RateLimiter::class);

        $rateLimiter->for('test', function ($job) {
            return Limit::none();
        });

        $this->assertJobRanSuccessfully(RateLimitedTestJob::class);
        $this->assertJobRanSuccessfully(RateLimitedTestJob::class);
    }

    public function testUnlimitedJobsAreExecutedUsingBackedEnum()
    {
        $rateLimiter = $this->app->make(RateLimiter::class);

        $rateLimiter->for(BackedEnumNamedRateLimited::FOO, function ($job) {
            return Limit::none();
        });

        $this->assertJobRanSuccessfully(RateLimitedTestJobUsingBackedEnum::class);
        $this->assertJobRanSuccessfully(RateLimitedTestJobUsingBackedEnum::class);
    }

    public function testUnlimitedJobsAreExecutedUsingUnitEnum()
    {
        $rateLimiter = $this->app->make(RateLimiter::class);

        $rateLimiter->for(UnitEnumNamedRateLimited::LARAVEL, function ($job) {
            return Limit::none();
        });

        $this->assertJobRanSuccessfully(RateLimitedTestJobUsingUnitEnum::class);
        $this->assertJobRanSuccessfully(RateLimitedTestJobUsingUnitEnum::class);
    }

    public function testRateLimitedJobsAreNotExecutedOnLimitReached2()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('get')->andReturn(0, 1, null);
        $cache->shouldReceive('add')->andReturn(true, true);
        $cache->shouldReceive('increment')->andReturn(1);
        $cache->shouldReceive('has')->andReturn(true);
        $cache->shouldReceive('getStore')->andReturn(new ArrayStore);

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

    public function testRateLimitedJobsCanBeSkippedOnLimitReachedAndReleasedAfter()
    {
        $rateLimiter = $this->app->make(RateLimiter::class);

        $rateLimiter->for('test', function ($job) {
            return Limit::perHour(1);
        });

        $this->assertJobRanSuccessfully(RateLimitedReleaseAfterTestJob::class);
        $this->assertJobWasReleasedAfter(RateLimitedReleaseAfterTestJob::class, 60);
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

    protected function assertJobWasReleasedAfter($class, $releaseAfter)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('release')->once()->withArgs([$releaseAfter]);
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

    public function testItCanLimitPerMinute()
    {
        Container::getInstance()->instance(RateLimiter::class, $limiter = new RateLimiter(new Repository(new ArrayStore)));
        $limiter->for('test', fn () => Limit::perMinute(3));
        $jobFactory = fn () => new class
        {
            public $released = false;

            public function release()
            {
                $this->released = true;
            }
        };
        $next = fn ($job) => $job;

        $middleware = new RateLimited('test');

        Carbon::setTestNow('2000-00-00 00:00:00.000');

        for ($i = 0; $i < 3; $i++) {
            $result = $middleware->handle($job = $jobFactory(), $next);
            $this->assertSame($job, $result);
            $this->assertFalse($job->released);

            Carbon::setTestNow(now()->addSeconds(1));
        }

        $result = $middleware->handle($job = $jobFactory(), $next);
        $this->assertNull($result);
        $this->assertTrue($job->released);

        Carbon::setTestNow('2000-00-00 00:00:59.999');

        $result = $middleware->handle($job = $jobFactory(), $next);
        $this->assertNull($result);
        $this->assertTrue($job->released);

        Carbon::setTestNow('2000-00-00 00:01:00.000');

        $result = $middleware->handle($job = $jobFactory(), $next);
        $this->assertSame($job, $result);
        $this->assertFalse($job->released);
    }

    public function testItCanLimitPerSecond()
    {
        Container::getInstance()->instance(RateLimiter::class, $limiter = new RateLimiter(new Repository(new ArrayStore)));
        $limiter->for('test', fn () => Limit::perSecond(3));
        $jobFactory = fn () => new class
        {
            public $released = false;

            public function release()
            {
                $this->released = true;
            }
        };
        $next = fn ($job) => $job;

        $middleware = new RateLimited('test');

        Carbon::setTestNow('2000-00-00 00:00:00.000');

        for ($i = 0; $i < 3; $i++) {
            $result = $middleware->handle($job = $jobFactory(), $next);
            $this->assertSame($job, $result);
            $this->assertFalse($job->released);

            Carbon::setTestNow(now()->addMilliseconds(100));
        }

        $result = $middleware->handle($job = $jobFactory(), $next);
        $this->assertNull($result);
        $this->assertTrue($job->released);

        Carbon::setTestNow('2000-00-00 00:00:00.999');

        $result = $middleware->handle($job = $jobFactory(), $next);
        $this->assertNull($result);
        $this->assertTrue($job->released);

        Carbon::setTestNow('2000-00-00 00:00:01.000');

        $result = $middleware->handle($job = $jobFactory(), $next);
        $this->assertSame($job, $result);
        $this->assertFalse($job->released);
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

class RateLimitedReleaseAfterTestJob extends RateLimitedTestJob
{
    public function middleware()
    {
        return [(new RateLimited('test'))->releaseAfter(60)];
    }
}

enum BackedEnumNamedRateLimited: string
{
    case FOO = 'bar';
}

enum UnitEnumNamedRateLimited
{
    case LARAVEL;
}

class RateLimitedTestJobUsingBackedEnum
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }

    public function middleware()
    {
        return [new RateLimited(BackedEnumNamedRateLimited::FOO)];
    }
}

class RateLimitedTestJobUsingUnitEnum
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }

    public function middleware()
    {
        return [new RateLimited(UnitEnumNamedRateLimited::LARAVEL)];
    }
}
