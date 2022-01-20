<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Redis\Factory as Redis;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimitedWithRedis;
use Illuminate\Support\Str;
use Mockery;
use Orchestra\Testbench\TestCase;

class RateLimitedWithRedisTest extends TestCase
{
    use InteractsWithRedis;

    protected function tearDown(): void
    {
        $this->tearDownRedis();
        Mockery::close();

        parent::tearDown();
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testUnlimitedJobsAreExecuted($connection)
    {
        $this->app['redis'] = $this->getRedisManager($connection);

        $rateLimiter = $this->app->make(RateLimiter::class);

        $testJob = new RedisRateLimitedTestJob;

        $rateLimiter->for($testJob->key, function ($job) {
            return Limit::none();
        });

        $this->assertJobRanSuccessfully($testJob);
        $this->assertJobRanSuccessfully($testJob);
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testRateLimitedJobsAreNotExecutedOnLimitReached($connection)
    {
        $this->app['redis'] = $this->getRedisManager($connection);

        $rateLimiter = $this->app->make(RateLimiter::class);

        $testJob = new RedisRateLimitedTestJob;

        $rateLimiter->for($testJob->key, function ($job) {
            return Limit::perMinute(1);
        });

        $this->assertJobRanSuccessfully($testJob);
        $this->assertJobWasReleased($testJob);
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testRateLimitedJobsCanBeSkippedOnLimitReached($connection)
    {
        $this->app['redis'] = $this->getRedisManager($connection);

        $rateLimiter = $this->app->make(RateLimiter::class);

        $testJob = new RedisRateLimitedDontReleaseTestJob;

        $rateLimiter->for($testJob->key, function ($job) {
            return Limit::perMinute(1);
        });

        $this->assertJobRanSuccessfully($testJob);
        $this->assertJobWasSkipped($testJob);
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testJobsCanHaveConditionalRateLimits($connection)
    {
        $this->app['redis'] = $this->getRedisManager($connection);

        $rateLimiter = $this->app->make(RateLimiter::class);

        $adminJob = new RedisAdminTestJob;

        $rateLimiter->for($adminJob->key, function ($job) {
            if ($job->isAdmin()) {
                return Limit::none();
            }

            return Limit::perMinute(1);
        });

        $this->assertJobRanSuccessfully($adminJob);
        $this->assertJobRanSuccessfully($adminJob);

        $nonAdminJob = new RedisNonAdminTestJob;

        $rateLimiter->for($nonAdminJob->key, function ($job) {
            if ($job->isAdmin()) {
                return Limit::none();
            }

            return Limit::perMinute(1);
        });

        $this->assertJobRanSuccessfully($nonAdminJob);
        $this->assertJobWasReleased($nonAdminJob);
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testMiddlewareSerialization($connection)
    {
        $this->app['redis'] = $this->getRedisManager($connection);

        $rateLimited = new RateLimitedWithRedis('limiterName');
        $rateLimited->shouldRelease = false;

        $restoredRateLimited = unserialize(serialize($rateLimited));

        $fetch = (function (string $name) {
            return $this->{$name};
        })->bindTo($restoredRateLimited, RateLimitedWithRedis::class);

        $this->assertFalse($restoredRateLimited->shouldRelease);
        $this->assertSame('limiterName', $fetch('limiterName'));
        $this->assertInstanceOf(RateLimiter::class, $fetch('limiter'));
        $this->assertInstanceOf(Redis::class, $fetch('redis'));
    }

    protected function assertJobRanSuccessfully($testJob)
    {
        $testJob::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = Mockery::mock(Job::class);

        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(false);
        $job->shouldReceive('delete')->once();

        $instance->call($job, [
            'command' => serialize($testJob),
        ]);

        $this->assertTrue($testJob::$handled);
    }

    protected function assertJobWasReleased($testJob)
    {
        $testJob::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = Mockery::mock(Job::class);

        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('release')->once();
        $job->shouldReceive('isReleased')->andReturn(true);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(true);

        $instance->call($job, [
            'command' => serialize($testJob),
        ]);

        $this->assertFalse($testJob::$handled);
    }

    protected function assertJobWasSkipped($testJob)
    {
        $testJob::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = Mockery::mock(Job::class);

        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(false);
        $job->shouldReceive('delete')->once();

        $instance->call($job, [
            'command' => serialize($testJob),
        ]);

        $this->assertFalse($testJob::$handled);
    }
}

class RedisRateLimitedTestJob
{
    use InteractsWithQueue, Queueable;

    public $key;

    public static $handled = false;

    public function __construct()
    {
        $this->key = Str::random(10);
    }

    public function handle()
    {
        static::$handled = true;
    }

    public function middleware()
    {
        return [new RateLimitedWithRedis($this->key)];
    }
}

class RedisAdminTestJob extends RedisRateLimitedTestJob
{
    public function isAdmin()
    {
        return true;
    }
}

class RedisNonAdminTestJob extends RedisRateLimitedTestJob
{
    public function isAdmin()
    {
        return false;
    }
}

class RedisRateLimitedDontReleaseTestJob extends RedisRateLimitedTestJob
{
    public function middleware()
    {
        return [(new RateLimitedWithRedis($this->key))->dontRelease()];
    }
}
