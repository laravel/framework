<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Queue\Events\QueuePaused;
use Illuminate\Queue\Events\QueuesPaused;
use Illuminate\Queue\Events\QueuesResumed;
use Illuminate\Queue\Worker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class QueuePauseCommandTest extends TestCase
{
    public function testDispatchesEvent()
    {
        Event::fake();

        $this->artisan('queue:pause default');

        Event::assertDispatched(QueuePaused::class);
    }

    public function testPausesAndResumesTheGivenConnectionAndQueue()
    {
        Event::fake();

        $this->artisan('queue:pause redis:emails')->assertSuccessful();

        Event::assertDispatched(QueuePaused::class, fn ($event) => $event->connectionName === 'redis' && $event->queue === 'emails');
        $this->assertTrue(Queue::isPaused('emails', 'redis'));

        $this->artisan('queue:resume redis:emails')->assertSuccessful();

        $this->assertFalse(Queue::isPaused('emails', 'redis'));
    }

    public function testPauseAllDispatchesEvent()
    {
        Event::fake();

        $this->artisan('queue:pause --all');

        Event::assertDispatched(QueuesPaused::class);
    }

    public function testResumeAllDispatchesEvent()
    {
        Event::fake();

        $this->artisan('queue:resume --all');

        Event::assertDispatched(QueuesResumed::class);
    }

    public function testDisabledError()
    {
        Event::fake();

        Worker::$pausable = false;

        $this->artisan('queue:pause default');

        Event::assertNotDispatched(QueuePaused::class);

        Worker::$pausable = true;
    }
}
