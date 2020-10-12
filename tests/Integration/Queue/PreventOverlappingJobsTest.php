<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\PreventOverlappingJobs;
use Mockery as m;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class PreventOverlappingJobsTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function testNonOverlappingJobsAreExecuted()
    {
        OverlappingTestJob::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->andReturn(false);
        $job->shouldReceive('delete')->once();

        $instance->call($job, [
            'command' => serialize($command = new OverlappingTestJob),
        ]);

        $lockKey = (new PreventOverlappingJobs)->getLockKey($command);

        $this->assertTrue(OverlappingTestJob::$handled);
        $this->assertTrue($this->app->get(Cache::class)->lock($lockKey, 10)->acquire());
    }

    public function testLockIsReleasedOnJobExceptions()
    {
        FailedOverlappingTestJob::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->andReturn(false);

        $this->expectException(\Exception::class);

        $instance->call($job, [
            'command' => serialize($command = new FailedOverlappingTestJob),
        ]);

        $lockKey = (new PreventOverlappingJobs)->getLockKey($command);

        $this->assertTrue(FailedOverlappingTestJob::$handled);
        $this->assertTrue($this->app->get(Cache::class)->lock($lockKey, 10)->acquire());
    }

    public function testOverlappingJobsAreNotExecuted()
    {
        OverlappingTestJob::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $lockKey = (new PreventOverlappingJobs)->getLockKey($command = new OverlappingTestJob);
        $this->app->get(Cache::class)->lock($lockKey, 10)->acquire();

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->andReturn(false);
        $job->shouldReceive('delete')->once();

        $instance->call($job, [
            'command' => serialize($command),
        ]);

        $this->assertFalse(OverlappingTestJob::$handled);
    }
}

class OverlappingTestJob
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }

    public function middleware()
    {
        return [new PreventOverlappingJobs];
    }
}

class FailedOverlappingTestJob extends OverlappingTestJob
{
    public function handle()
    {
        static::$handled = true;

        throw new \Exception;
    }
}
