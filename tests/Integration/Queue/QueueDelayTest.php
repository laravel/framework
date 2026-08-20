<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Support\Facades\Queue;
use Illuminate\Tests\App\Jobs\DelayedJob;
use Orchestra\Testbench\TestCase;

class QueueDelayTest extends TestCase
{
    public function test_queue_delay()
    {
        Queue::fake();

        $job = new DelayedJob;

        dispatch($job);

        $this->assertEquals(60, $job->delay);
    }

    public function test_queue_without_delay()
    {
        Queue::fake();

        $job = new DelayedJob;

        dispatch($job->withoutDelay());

        $this->assertEquals(0, $job->delay);
    }

    public function test_pending_dispatch_without_delay()
    {
        Queue::fake();

        $job = new DelayedJob;

        dispatch($job)->withoutDelay();

        $this->assertEquals(0, $job->delay);
    }
}
