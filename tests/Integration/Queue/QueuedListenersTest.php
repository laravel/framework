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

        Queue::assertNotPushed(CallQueuedListener::class, function ($job) {
            return $job->class == QueuedListenersTestListenerShouldNotQueue::class;
        });
    }

    public function testAssertListenerPushed()
    {
        Queue::fake();

        Event::listen(QueuedListenersTestEvent::class, QueuedListenersTestListenerShouldQueue::class);

        Event::dispatch(
            new QueuedListenersTestEvent
        );

        Queue::assertListenerPushed(QueuedListenersTestListenerShouldQueue::class);
    }

    public function testAssertListenerPushedTimes()
    {
        Queue::fake();

        Event::listen(QueuedListenersTestEvent::class, QueuedListenersTestListenerShouldQueue::class);
        Event::listen(QueuedListenersTestEvent::class, QueuedListenersTestListenerShouldQueue::class);
        Event::listen(QueuedListenersTestEvent::class, QueuedListenersTestListenerShouldQueue::class);

        Event::dispatch(
            new QueuedListenersTestEvent
        );

        Queue::assertListenerPushed(QueuedListenersTestListenerShouldQueue::class, 3);
    }

    public function testAssertListenerPushedWithCallback()
    {
        Queue::fake();

        Event::listen(QueuedListenersTestEventWithAttributes::class, QueuedListenersTestListenerShouldQueue::class);

        Event::dispatch(
            new QueuedListenersTestEventWithAttributes
        );

        Queue::assertListenerPushed(QueuedListenersTestListenerShouldQueue::class, function ($job) {
            $this->assertTrue($job instanceof QueuedListenersTestEventWithAttributes);
            $this->assertSame('first', $job->firstAttribute);
            $this->assertSame('second', $job->secondAttribute);

            return true;
        });
    }
}

class QueuedListenersTestEvent
{
    //
}

class QueuedListenersTestEventWithAttributes
{
    public $firstAttribute = 'first';
    public $secondAttribute = 'second';
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
