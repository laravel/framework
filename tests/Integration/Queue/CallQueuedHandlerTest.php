<?php

namespace Illuminate\Tests\Integration\Queue;

use Mockery;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Queue\Events\JobFailed;

/**
 * @group integration
 */
class CallQueuedHandlerTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();

        Mockery::close();
    }

    public function test_job_can_be_dispatched()
    {
        CallQueuedHandlerTestJob::$handled = false;

        $instance = new \Illuminate\Queue\CallQueuedHandler(new \Illuminate\Bus\Dispatcher(app()));

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

    public function test_job_is_marked_as_failed_if_model_not_found_exception_is_thrown()
    {
        Event::fake();

        $instance = new \Illuminate\Queue\CallQueuedHandler(new \Illuminate\Bus\Dispatcher(app()));

        $job = Mockery::mock('Illuminate\Contracts\Queue\Job');
        $job->shouldReceive('getConnectionName')->andReturn('connection');
        $job->shouldReceive('resolveName')->andReturn(__CLASS__);
        $job->shouldReceive('markAsFailed')->once();
        $job->shouldReceive('isDeleted')->andReturn(false);
        $job->shouldReceive('delete')->once();
        $job->shouldReceive('failed')->once();

        $instance->call($job, [
            'command' => serialize(new CallQueuedHandlerExceptionThrower),
        ]);

        Event::assertDispatched(JobFailed::class);
    }

    public function test_job_is_deleted_if_has_delete_property()
    {
        Event::fake();

        $instance = new \Illuminate\Queue\CallQueuedHandler(new \Illuminate\Bus\Dispatcher(app()));

        $job = Mockery::mock('Illuminate\Contracts\Queue\Job');
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
    use \Illuminate\Queue\InteractsWithQueue;

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
        throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Foo');
    }
}
