<?php

use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class CallQueuedHandlerTest extends TestCase
{
    public function test_job_can_be_dispatched()
    {
        CallQueuedHandlerTestJob::$handled = false;

        $instance = new Illuminate\Queue\CallQueuedHandler(new Illuminate\Bus\Dispatcher(app()));

        $job = Mockery::mock('Illuminate\Contracts\Queue\Job');
        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('isDeleted')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->andReturn(false);
        $job->shouldReceive('delete')->once();

        $instance->call($job, [
            'command' => serialize(new CallQueuedHandlerTestJob),
        ]);

        $this->assertTrue(CallQueuedHandlerTestJob::$handled);
    }

    public function test_job_is_not_dispatched_if_it_has_already_been_deleted()
    {
        // It may be possible for the job to be deleted during unserialization, so we dont
        // want to fire the job in that scenario...

        CallQueuedHandlerTestJob::$handled = false;

        $instance = new Illuminate\Queue\CallQueuedHandler(new Illuminate\Bus\Dispatcher(app()));

        $job = Mockery::mock('Illuminate\Contracts\Queue\Job');
        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeleted')->andReturn(true);
        $job->shouldReceive('isDeletedOrReleased')->andReturn(true);

        $instance->call($job, [
            'command' => serialize(new CallQueuedHandlerTestJob),
        ]);

        $this->assertFalse(CallQueuedHandlerTestJob::$handled);
    }
}

class CallQueuedHandlerTestJob
{
    use Illuminate\Queue\InteractsWithQueue;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }
}
