<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class QueueDelayTest extends TestCase
{
    public function test_queue_delay()
    {
        Queue::fake();

        $job = new TestJob;

        dispatch($job);

        $this->assertEquals(60, $job->delay);
    }

    public function test_queue_without_delay()
    {
        Queue::fake();

        $job = new TestJob;

        dispatch($job->withoutDelay());

        $this->assertEquals(0, $job->delay);
    }

    public function test_pending_dispatch_without_delay()
    {
        Queue::fake();

        $job = new TestJob;

        dispatch($job)->withoutDelay();

        $this->assertEquals(0, $job->delay);
    }

    public function test_queue_with_custom_delay()
    {
        Queue::fake();

        $job = new TestJob;
        $customDelay = 120;

        dispatch($job->delay($customDelay));

        $this->assertEquals($customDelay, $job->delay);
    }

    public function test_queue_with_zero_delay()
    {
        Queue::fake();

        $job = new TestJob;

        dispatch($job->delay(0));

        $this->assertEquals(0, $job->delay);
    }

    public function test_queue_with_negative_delay()
    {
        Queue::fake();

        $job = new TestJob;

        dispatch($job->delay(-30));

        $this->assertEquals(0, $job->delay);
    }

    public function test_chaining_multiple_delay_calls()
    {
        Queue::fake();

        $job = new TestJob;

        dispatch($job->delay(30)->delay(90));

        $this->assertEquals(90, $job->delay);
    }
}

class TestJob implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->delay(60);
    }
}
