<?php

namespace Illuminate\Tests\Integration\Queue;

use Event;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Orchestra\Testbench\TestCase;
use Queue;

class QueuedListenersTest extends TestCase
{
    public function testListenersCanBeQueuedOptionally()
    {
        Queue::fake();

        Event::listen(QueuedListenersTestEvent::class, QueuedListenersTestListenerShouldQueue::class);
        Event::listen(QueuedListenersTestEvent::class, QueuedListenersTestListenerShouldNotQueue::class);

        Event::dispatch(
            new QueuedListenersTestEvent
        );

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class == QueuedListenersTestListenerShouldQueue::class;
        });

        $this->assertCount(1, Queue::listenersPushed(QueuedListenersTestListenerShouldQueue::class));
        $this->assertCount(
            0,
            Queue::listenersPushed(
                QueuedListenersTestListenerShouldQueue::class,
                fn ($event, $handler, $queue, $data) => $queue === 'not-a-real-queue'
            )
        );
        $this->assertCount(
            1,
            Queue::listenersPushed(
                QueuedListenersTestListenerShouldQueue::class,
                fn (QueuedListenersTestEvent $event) => $event->value === 100
            )
        );

        Queue::assertNotPushed(CallQueuedListener::class, function ($job) {
            return $job->class == QueuedListenersTestListenerShouldNotQueue::class;
        });
        $this->assertCount(0, Queue::listenersPushed(QueuedListenersTestListenerShouldNotQueue::class));
    }
}

class QueuedListenersTestEvent
{
    public int $value = 100;
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
