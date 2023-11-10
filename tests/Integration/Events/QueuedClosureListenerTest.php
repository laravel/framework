<?php

namespace Illuminate\Tests\Integration\Events;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Events\InvokeQueuedClosure;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class QueuedClosureListenerTest extends TestCase
{
    public function testAnonymousQueuedListenerIsQueued()
    {
        Bus::fake();

        Event::listen(\Illuminate\Events\queueable(function (TestEvent $event) {
            //
        })->catch(function (TestEvent $event) {
            //
        })->onConnection(null)->onQueue(null));

        Event::dispatch(new TestEvent);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class == InvokeQueuedClosure::class;
        });
    }
}

class TestEvent
{
    //
}
