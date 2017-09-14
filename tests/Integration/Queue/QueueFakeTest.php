<?php

namespace Illuminate\Tests\Integration\Queue;

use Orchestra\Testbench\TestCase;
use Illuminate\Bus\BusServiceProvider;
use Illuminate\Support\Facades\Queue as QueueFacade;

/**
 * @group integration
 */
class QueueFakeTest extends TestCase
{
    public function setUp()
    {
        (new BusServiceProvider(app()))->register();
        CallQueuedHandlerTestJob::$handled = false;
    }

    public function test_job_through_helper_is_not_fired()
    {
        QueueFacade::fake();

        dispatch(new CallQueuedHandlerTestJob);

        // job should be puzshed to the QueueFake and not actually handled
        QueueFacade::assertPushed(CallQueuedHandlerTestJob::class);
        $this->assertFalse(CallQueuedHandlerTestJob::$handled);
    }

    public function test_job_through_facade_is_not_fired()
    {
        QueueFacade::fake();

        QueueFacade::push(new CallQueuedHandlerTestJob);

        // job should be puzshed to the QueueFake and not actually handled
        QueueFacade::assertPushed(CallQueuedHandlerTestJob::class);
        $this->assertFalse(CallQueuedHandlerTestJob::$handled);
    }

    public function test_job_through_static_is_not_fired()
    {
        QueueFacade::fake();

        CallQueuedHandlerTestJob::dispatch();

        // job should be puzshed to the QueueFake and not actually handled
        QueueFacade::assertPushed(CallQueuedHandlerTestJob::class);
        $this->assertFalse(CallQueuedHandlerTestJob::$handled);
    }
}

class CallQueuedHandlerTestJob
{
    use \Illuminate\Queue\InteractsWithQueue, \Illuminate\Foundation\Bus\Dispatchable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }
}
