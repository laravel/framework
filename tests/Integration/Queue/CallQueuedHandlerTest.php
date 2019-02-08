<?php

namespace Illuminate\Tests\Integration\Queue;

use Mockery as m;
use Illuminate\Bus\Dispatcher;
use Orchestra\Testbench\TestCase;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\Event;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @group integration
 */
class CallQueuedHandlerTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function test_job_can_be_dispatched()
    {
        CallQueuedHandlerTestJob::$handled = false;

        $instance = new CallQueuedHandler(new Dispatcher(app()));

        $job = m::mock(Job::class);
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

    public function test_job_is_marked_as_failed_if_model_not_found_exception_is_thrown()
    {
        $instance = new CallQueuedHandler(new Dispatcher(app()));

        $job = m::mock(Job::class);
        $job->shouldReceive('resolveName')->andReturn(__CLASS__);
        $job->shouldReceive('fail')->once();

        $instance->call($job, [
            'command' => serialize(new CallQueuedHandlerExceptionThrower),
        ]);
    }

    public function test_job_is_deleted_if_has_delete_property()
    {
        Event::fake();

        $instance = new CallQueuedHandler(new Dispatcher(app()));

        $job = m::mock(Job::class);
        $job->shouldReceive('getConnectionName')->andReturn('connection');
        $job->shouldReceive('resolveName')->andReturn(CallQueuedHandlerExceptionThrower::class);
        $job->shouldReceive('markAsFailed')->never();
        $job->shouldReceive('isDeleted')->andReturn(false);
        $job->shouldReceive('delete')->once();
        $job->shouldReceive('failed')->never();

        $instance->call($job, [
            'command' => serialize(new CallQueuedHandlerExceptionThrower),
        ]);

        Event::assertNotDispatched(JobFailed::class);
    }
}

class CallQueuedHandlerTestJob
{
    use InteractsWithQueue;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }
}

class CallQueuedHandlerExceptionThrower
{
    public $deleteWhenMissingModels = true;

    public function handle()
    {
        //
    }

    public function __wakeup()
    {
        throw new ModelNotFoundException('Foo');
    }
}
