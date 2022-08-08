<?php

namespace Illuminate\Tests\Integration\Broadcasting;

use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class BroadcastManagerTest extends TestCase
{
    public function testEventCanBeBroadcastNow()
    {
        Bus::fake();
        Queue::fake();

        Broadcast::queue(new TestEventNow);

        Bus::assertDispatched(BroadcastEvent::class);
        Queue::assertNotPushed(BroadcastEvent::class);
    }

    public function testEventsCanBeBroadcast()
    {
        Bus::fake();
        Queue::fake();

        Broadcast::queue(new TestEvent);

        Bus::assertNotDispatched(BroadcastEvent::class);
        Queue::assertPushed(BroadcastEvent::class);
    }

    public function testUniqueEventsCanBeBroadcast()
    {
        Bus::fake();
        Queue::fake();

        Broadcast::queue(new TestEventUnique);

        Bus::assertNotDispatched(BroadcastEvent::class);
        Queue::assertPushed(BroadcastEvent::class);

        $lockKey = 'laravel_unique_job:'.TestEventUnique::class;
        $this->assertFalse($this->app->get(Cache::class)->lock($lockKey, 10)->get());
    }
}

class TestEvent implements ShouldBroadcast
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\Channel[]
     */
    public function broadcastOn()
    {
        //
    }
}

class TestEventNow implements ShouldBroadcastNow
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\Channel[]
     */
    public function broadcastOn()
    {
        //
    }
}

class TestEventUnique implements ShouldBroadcast, ShouldBeUnique
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\Channel[]
     */
    public function broadcastOn()
    {
        //
    }
}
