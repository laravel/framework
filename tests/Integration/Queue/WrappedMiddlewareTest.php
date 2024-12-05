<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WrappedMiddleware;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class WrappedMiddlewareTest extends TestCase
{
    public function testBeforeAndAfterCalled()
    {
        $job = new WrappedMiddlewareTestJob();

        $this->assertJobRanSuccessfully($job);
        $this->assertContains('before', $job::$calls);
        $this->assertContains('middlewareBefore', $job::$calls);
        $this->assertContains('middlewareAfter', $job::$calls);
        $this->assertContains('after', $job::$calls);
        $this->assertNotContains('onFail', $job::$calls);
        $this->assertSame(['before', 'middlewareBefore', 'after', 'jobHandle', 'middlewareAfter'], $job::$calls);
    }

    public function testBeforeCalledWhenMiddlewareReleases()
    {
        $job = new WrappedMiddlewareTestJob();

        $this->assertReleasedInMiddleware($job);
        $this->assertContains('before', $job::$calls);
        $this->assertContains('middlewareBefore', $job::$calls);
        $this->assertNotContains('middlewareAfter', $job::$calls);
        $this->assertNotContains('after', $job::$calls);
        $this->assertContains('onFail', $job::$calls);
        $this->assertSame(['before', 'middlewareBefore', 'onFail'], $job::$calls);
    }

    public function testBeforeAndAfterCalledWhenJobReleases()
    {
        $job = new WrappedMiddlewareTestJob();

        $this->assertReleasedInJob($job);
        $this->assertContains('before', $job::$calls);
        $this->assertContains('middlewareBefore', $job::$calls);
        $this->assertContains('middlewareAfter', $job::$calls);
        $this->assertContains('after', $job::$calls);
        $this->assertNotContains('onFail', $job::$calls);
        $this->assertSame(['before', 'middlewareBefore', 'after', 'jobHandle', 'middlewareAfter'], $job::$calls);
    }

    public function testBeforeCalledWhenMiddlewareFails()
    {
        $job = new WrappedMiddlewareTestJob();

        $this->assertFailedInMiddleware($job);
        $this->assertContains('before', $job::$calls);
        $this->assertContains('middlewareBefore', $job::$calls);
        $this->assertNotContains('middlewareAfter', $job::$calls);
        $this->assertNotContains('after', $job::$calls);
        $this->assertContains('onFail', $job::$calls);
        $this->assertSame(['before', 'middlewareBefore', 'onFail'], $job::$calls);
    }

    public function testBeforeMethodCanShortCircuitMiddleware()
    {
        $job = new WrappedMiddlewareTestJob();

        $this->assertMiddlewareSkipped($job);
        $this->assertContains('before', $job::$calls);
        $this->assertNotContains('middlewareBefore', $job::$calls);
        $this->assertNotContains('middlewareAfter', $job::$calls);
        $this->assertNotContains('after', $job::$calls);
        $this->assertNotContains('onFail', $job::$calls);
        $this->assertSame(['before'], $job::$calls);
    }

    protected function assertJobRanSuccessfully($class)
    {
        $class::$calls = [];
        $class::$before = fn ($job) => $job::$calls[] = 'before';
        $class::$after = fn ($job) => $job::$calls[] = 'after';
        $class::$onFail = fn ($job) => $job::$calls[] = 'onFail';
        $class::$shouldRelease = false;
        $class::$middlewareToUse = DefaultMiddlewareToBeWrapped::class;

        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->andReturn(false);
        $job->shouldReceive('delete')->once();
        $job->shouldReceive('uuid')->andReturn('simple-test-uuid');

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertContains('jobHandle', $class::$calls);
    }

    protected function assertReleasedInMiddleware($class)
    {
        $class::$calls = [];
        $class::$before = fn ($job) => $job::$calls[] = 'before';
        $class::$after = fn ($job) => $job::$calls[] = 'after';
        $class::$onFail = fn ($job) => $job::$calls[] = 'onFail';
        $class::$shouldRelease = false;
        $class::$middlewareToUse = ReleaseMiddleware::class;

        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);
        $job->shouldReceive('release')->once();

        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(true);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(false);
        $job->shouldReceive('delete')->once();
        $job->shouldReceive('uuid')->andReturn('simple-test-uuid');

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertNotContains('jobHandle', $class::$calls);
    }

    protected function assertReleasedInJob($class)
    {
        $class::$calls = [];
        $class::$before = fn ($job) => $job::$calls[] = 'before';
        $class::$after = fn ($job) => $job::$calls[] = 'after';
        $class::$onFail = fn ($job) => $job::$calls[] = 'onFail';
        $class::$shouldRelease = true;
        $class::$middlewareToUse = DefaultMiddlewareToBeWrapped::class;

        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);
        $job->shouldReceive('release')->once();

        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(true);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(false);
        $job->shouldReceive('delete')->once();
        $job->shouldReceive('uuid')->andReturn('simple-test-uuid');

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertContains('jobHandle', $class::$calls);
    }

    protected function assertFailedInMiddleware($class)
    {
        $class::$calls = [];
        $class::$before = fn ($job) => $job::$calls[] = 'before';
        $class::$after = fn ($job) => $job::$calls[] = 'after';
        $class::$onFail = fn ($job) => $job::$calls[] = 'onFail';
        $class::$shouldRelease = false;
        $class::$middlewareToUse = FailureMiddleware::class;

        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);
        $job->shouldReceive('fail')->once();

        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('hasFailed')->andReturn(true);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(false);
        $job->shouldReceive('delete')->once();
        $job->shouldReceive('uuid')->andReturn('simple-test-uuid');

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertNotContains('jobHandle', $class::$calls);
    }

    protected function assertMiddlewareSkipped($class)
    {
        $class::$calls = [];
        $class::$before = function ($job) {
            $job::$calls[] = 'before';

            return false;
        };
        $class::$after = fn ($job) => $job::$calls[] = 'after';
        $class::$onFail = fn ($job) => $job::$calls[] = 'onFail';
        $class::$shouldRelease = false;
        $class::$middlewareToUse = DefaultMiddlewareToBeWrapped::class;

        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);
        $job->shouldNotReceive('release');
        $job->shouldNotReceive('fail');

        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(false);
        $job->shouldReceive('delete')->once();
        $job->shouldReceive('uuid')->andReturn('simple-test-uuid');

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertNotContains('jobHandle', $class::$calls);
    }
}

class DefaultMiddlewareToBeWrapped
{
    public function handle($job, $next): void
    {
        $job::$calls[] = 'middlewareBefore';

        $next($job);

        $job::$calls[] = 'middlewareAfter';
    }
}

class ReleaseMiddleware
{
    public function handle($job, $next): void
    {
        $job::$calls[] = 'middlewareBefore';

        $job->release(1);
    }
}

class FailureMiddleware
{
    public function handle($job, $next): void
    {
        $job::$calls[] = 'middlewareBefore';

        $job->fail(new \Exception('Failed'));
    }
}

class WrappedMiddlewareTestJob
{
    use InteractsWithQueue, Queueable;

    public static $calls = [];

    public static $before;

    public static $after;

    public static $onFail;

    public static $middlewareToUse = DefaultMiddlewareToBeWrapped::class;

    public static $shouldRelease = false;

    public function handle()
    {
        self::$calls[] = 'jobHandle';

        if (self::$shouldRelease) {
            $this->release(1);
        }
    }

    public function middleware(): array
    {
        return [
            (new WrappedMiddleware(new self::$middlewareToUse))
                ->before(self::$before)
                ->after(self::$after)
                ->onFail(self::$onFail),
        ];
    }
}
