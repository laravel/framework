<?php

namespace Illuminate\Tests\Integration\Queue;

use Orchestra\Testbench\TestCase;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * @group integration
 */
class QueuedListenersTest extends TestCase
{
    public function test_listeners_can_be_queued_optionally()
    {
        \Queue::fake();

        \Event::listen(QueuedListenersTestEvent::class, QueuedListenersTestListenerShouldQueue::class);
        \Event::listen(QueuedListenersTestEvent::class, QueuedListenersTestListenerShouldNotQueue::class);

        \Event::dispatch(
            new QueuedListenersTestEvent()
        );

        \Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class == QueuedListenersTestListenerShouldQueue::class;
        });

        \Queue::assertNotPushed(CallQueuedListener::class, function ($job) {
            return $job->class == QueuedListenersTestListenerShouldNotQueue::class;
        });
    }
}

class QueuedListenersTestEvent
{
}

class QueuedListenersTestListenerShouldQueue implements ShouldQueue
{
    public function shouldQueue()
    {
        return true;
    }
}

class QueuedListenersTestListenerShouldNotQueue implements ShouldQueue
{
    public function shouldQueue()
    {
        return false;
    }
}
