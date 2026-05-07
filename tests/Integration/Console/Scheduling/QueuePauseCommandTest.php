<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Queue\Events\QueuePaused;
use Illuminate\Queue\Worker;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class QueuePauseCommandTest extends TestCase
{
    public function testDispatchesEvent()
    {
        Event::fake();

        $this->artisan('queue:pause');

        Event::assertDispatched(QueuePaused::class);
    }

    public function testDisabledError()
    {
        Event::fake();

        Worker::$pausable = false;

        $this->artisan('queue:pause');

        Event::assertNotDispatched(QueuePaused::class);

        Worker::$pausable = true;
    }
}
