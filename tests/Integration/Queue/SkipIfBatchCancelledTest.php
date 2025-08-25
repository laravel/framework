<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class SkipIfBatchCancelledTest extends TestCase
{
    public function testJobsAreSkippedOnceBatchIsCancelled()
    {
        [$beforeCancelled] = (new SkipCancelledBatchableTestJob())->withFakeBatch();
        [$afterCancelled] = (new SkipCancelledBatchableTestJob())->withFakeBatch(
            cancelledAt: \Carbon\CarbonImmutable::now()
        );

        $this->assertJobRanSuccessfully($beforeCancelled);
        $this->assertJobWasSkipped($afterCancelled);
    }

    protected function assertJobRanSuccessfully($class)
    {
        $this->assertJobHandled($class, true);
    }

    protected function assertJobWasSkipped($class)
    {
        $this->assertJobHandled($class, false);
    }

    protected function assertJobHandled($class, $expectedHandledValue)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('uuid')->once()->andReturn('simple-test-uuid');
        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(false);
        $job->shouldReceive('delete')->once();

        $instance->call($job, [
            'command' => serialize($command = $class),
        ]);

        $this->assertEquals($expectedHandledValue, $class::$handled);
    }
}

class SkipCancelledBatchableTestJob
{
    use Batchable, InteractsWithQueue, Queueable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }

    public function middleware()
    {
        return [new SkipIfBatchCancelled];
    }
}
