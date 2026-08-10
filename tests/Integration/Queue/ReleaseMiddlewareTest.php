<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\Release;
use Laravel\SerializableClosure\SerializableClosure;
use Mockery;
use Orchestra\Testbench\TestCase;

class ReleaseMiddlewareTest extends TestCase
{
    public function testJobIsReleasedWhenConditionIsTrue()
    {
        $job = new ReleaseTestJob(release: true, releaseAfter: 60);

        $this->assertJobWasReleased($job, releaseAfter: 60);
    }

    public function testJobIsReleasedWhenConditionIsTrueUsingClosure()
    {
        $job = new ReleaseTestJob(release: new SerializableClosure(fn () => true), releaseAfter: 60);

        $this->assertJobWasReleased($job, releaseAfter: 60);
    }

    public function testJobRunsWhenConditionIsFalse()
    {
        $job = new ReleaseTestJob(release: false);

        $this->assertJobRanSuccessfully($job);
    }

    public function testJobRunsWhenConditionIsFalseUsingClosure()
    {
        $job = new ReleaseTestJob(release: new SerializableClosure(fn () => false));

        $this->assertJobRanSuccessfully($job);
    }

    public function testJobIsReleasedWithoutDelayByDefault()
    {
        $job = new ReleaseTestJob(release: true);

        $this->assertJobWasReleased($job, releaseAfter: 0);
    }

    public function testJobRunsWhenConditionIsTrueWithUnless()
    {
        $job = new ReleaseTestJob(release: true, useUnless: true);

        $this->assertJobRanSuccessfully($job);
    }

    public function testJobRunsWhenConditionIsTrueWithUnlessUsingClosure()
    {
        $job = new ReleaseTestJob(release: new SerializableClosure(fn () => true), useUnless: true);

        $this->assertJobRanSuccessfully($job);
    }

    public function testJobIsReleasedWhenConditionIsFalseWithUnless()
    {
        $job = new ReleaseTestJob(release: false, useUnless: true, releaseAfter: 60);

        $this->assertJobWasReleased($job, releaseAfter: 60);
    }

    public function testJobIsReleasedWhenConditionIsFalseWithUnlessUsingClosure()
    {
        $job = new ReleaseTestJob(release: new SerializableClosure(fn () => false), useUnless: true, releaseAfter: 60);

        $this->assertJobWasReleased($job, releaseAfter: 60);
    }

    protected function assertJobRanSuccessfully(ReleaseTestJob $class)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = Mockery::mock(Job::class);

        $job->expects('hasFailed')->andReturn(false);
        $job->expects('isReleased')->times(2)->andReturn(false);
        $job->expects('isDeletedOrReleased')->andReturn(false);
        $job->expects('delete');
        $job->shouldReceive('release')->never();

        $instance->call($job, [
            'command' => serialize($class),
        ]);

        $this->assertTrue($class::$handled);
    }

    protected function assertJobWasReleased(ReleaseTestJob $class, int $releaseAfter = 0)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = Mockery::mock(Job::class);

        $job->expects('hasFailed')->andReturn(false);
        $job->expects('isReleased')->times(2)->andReturn(true);
        $job->expects('isDeletedOrReleased')->andReturn(true);
        $job->expects('release')->with($releaseAfter);
        $job->shouldReceive('delete')->never();

        $instance->call($job, [
            'command' => serialize($class),
        ]);

        $this->assertFalse($class::$handled);
    }
}

class ReleaseTestJob
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public function __construct(
        protected bool|SerializableClosure $release,
        protected bool $useUnless = false,
        protected int $releaseAfter = 0,
    ) {
    }

    public function handle(): void
    {
        static::$handled = true;
    }

    public function middleware(): array
    {
        $release = $this->release instanceof SerializableClosure
            ? $this->release->getClosure()
            : $this->release;

        if ($this->useUnless) {
            return [Release::unless($release, $this->releaseAfter)];
        }

        return [Release::when($release, $this->releaseAfter)];
    }
}
