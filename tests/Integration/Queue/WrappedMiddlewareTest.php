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

        $this->assertMiddlewareShortCircuited($job);
        $this->assertContains('before', $job::$calls);
        $this->assertNotContains('middlewareBefore', $job::$calls);
        $this->assertNotContains('middlewareAfter', $job::$calls);
        $this->assertNotContains('after', $job::$calls);
        $this->assertNotContains('onFail', $job::$calls);
        $this->assertSame(['before'], $job::$calls);
    }

    public function testMiddlewareCanBeSkipped()
    {
        $job = new WrappedMiddlewareTestJob();

        $this->assertMiddlewareSkipped($job, true);
        $this->assertNotContains('before', $job::$calls);
        $this->assertNotContains('middlewareBefore', $job::$calls);
        $this->assertNotContains('middlewareAfter', $job::$calls);
        $this->assertNotContains('after', $job::$calls);
        $this->assertNotContains('onFail', $job::$calls);
        $this->assertSame(['jobHandle'], $job::$calls);

        $this->assertMiddlewareSkipped($job, false);
        $this->assertContains('before', $job::$calls);
        $this->assertContains('middlewareBefore', $job::$calls);
        $this->assertContains('middlewareAfter', $job::$calls);
        $this->assertContains('after', $job::$calls);
        $this->assertNotContains('onFail', $job::$calls);
        $this->assertSame(['before', 'middlewareBefore', 'after', 'jobHandle', 'middlewareAfter'], $job::$calls);

        $this->assertMiddlewareSkippedUnless($job, false);
        $this->assertNotContains('before', $job::$calls);
        $this->assertNotContains('middlewareBefore', $job::$calls);
        $this->assertNotContains('middlewareAfter', $job::$calls);
        $this->assertNotContains('after', $job::$calls);
        $this->assertNotContains('onFail', $job::$calls);
        $this->assertSame(['jobHandle'], $job::$calls);

        $this->assertMiddlewareSkippedUnless($job, true);
        $this->assertContains('before', $job::$calls);
        $this->assertContains('middlewareBefore', $job::$calls);
        $this->assertContains('middlewareAfter', $job::$calls);
        $this->assertContains('after', $job::$calls);
        $this->assertNotContains('onFail', $job::$calls);
        $this->assertSame(['before', 'middlewareBefore', 'after', 'jobHandle', 'middlewareAfter'], $job::$calls);
    }

    protected function assertJobRanSuccessfully($class)
    {
        $class::$calls = [];
        $class::$shouldRelease = false;
        $class::$middlewareToUse = [
            (new WrappedMiddleware(new DefaultMiddleware()))
                ->before(fn ($job) => $job::$calls[] = 'before')
                ->after(fn ($job) => $job::$calls[] = 'after')
                ->onFail(fn ($job) => $job::$calls[] = 'onFail'),
        ];

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
        $class::$shouldRelease = false;
        $class::$middlewareToUse = [
            (new WrappedMiddleware(new ReleaseMiddleware))
                ->before(fn ($job) => $job::$calls[] = 'before')
                ->after(fn ($job) => $job::$calls[] = 'after')
                ->onFail(fn ($job) => $job::$calls[] = 'onFail'),
        ];

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
        $class::$shouldRelease = true;
        $class::$middlewareToUse = [
            (new WrappedMiddleware(new DefaultMiddleware))
                ->before(fn ($job) => $job::$calls[] = 'before')
                ->after(fn ($job) => $job::$calls[] = 'after')
                ->onFail(fn ($job) => $job::$calls[] = 'onFail'),
        ];

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
        $class::$shouldRelease = false;
        $class::$middlewareToUse = [
            (new WrappedMiddleware(new FailureMiddleware))
                ->before(fn ($job) => $job::$calls[] = 'before')
                ->after(fn ($job) => $job::$calls[] = 'after')
                ->onFail(fn ($job) => $job::$calls[] = 'onFail'),
        ];

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

    protected function assertMiddlewareShortCircuited($class)
    {
        $class::$calls = [];
        $class::$shouldRelease = false;
        $class::$middlewareToUse = [
            (new WrappedMiddleware(new DefaultMiddleware))
                ->before(function ($job) {
                    $job::$calls[] = 'before';

                    return false;
                })
                ->after(fn ($job) => $job::$calls[] = 'after')
                ->onFail(fn ($job) => $job::$calls[] = 'onFail'),
        ];

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

    protected function assertMiddlewareSkipped($class, bool $skip = true)
    {
        $class::$calls = [];
        $class::$shouldRelease = false;
        $class::$middlewareToUse = [
            (new WrappedMiddleware(new DefaultMiddleware))
                ->before(fn ($job) => $job::$calls[] = 'before')
                ->after(fn ($job) => $job::$calls[] = 'after')
                ->onFail(fn ($job) => $job::$calls[] = 'onFail')
                ->skipWhen($skip),
        ];

        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);
        $job->shouldNotReceive('release');
        $job->shouldNotReceive('fail');

        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(false);
        $job->shouldReceive('delete')->once();
        $job->shouldReceive('uuid')->andReturn('simple-test-uuid');

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertContains('jobHandle', $class::$calls);
    }

    protected function assertMiddlewareSkippedUnless($class, bool $unless = true)
    {
        $class::$calls = [];
        $class::$shouldRelease = false;
        $class::$middlewareToUse = [
            (new WrappedMiddleware(new DefaultMiddleware))
                ->before(fn ($job) => $job::$calls[] = 'before')
                ->after(fn ($job) => $job::$calls[] = 'after')
                ->onFail(fn ($job) => $job::$calls[] = 'onFail')
                ->skipUnless($unless),
        ];

        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);
        $job->shouldNotReceive('release');
        $job->shouldNotReceive('fail');

        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(false);
        $job->shouldReceive('delete')->once();
        $job->shouldReceive('uuid')->andReturn('simple-test-uuid');

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertContains('jobHandle', $class::$calls);
    }
}

class DefaultMiddleware
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

    public static $middlewareToUse;

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
        return self::$middlewareToUse;
    }
}
