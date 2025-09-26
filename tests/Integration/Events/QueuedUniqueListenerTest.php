<?php

namespace Illuminate\Tests\Integration\Events;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class QueuedUniqueListenerTest extends TestCase
{
    public function testUniqueListenersPushedToQueue()
    {
        Event::listen(UniqueListenerTestEvent::class, UniqueListener::class);

        Queue::fake();

        UniqueListenerTestEvent::dispatch();
        UniqueListenerTestEvent::dispatch();
        UniqueListenerTestEvent::dispatch();

        Queue::assertPushed(CallQueuedListener::class, 1);
    }
}

class UniqueListenerTestEvent
{
    use Dispatchable;
}

class UniqueListener implements ShouldQueue, ShouldBeUnique
{
    public function handle()
    {
    }
}
