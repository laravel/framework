<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class QueueSizeTest extends TestCase
{
    public function test_queue_size()
    {
        Queue::fake();

        $this->assertEquals(0, Queue::size());
        $this->assertEquals(0, Queue::size('Q2'));

        $job = new TestJob1;

        dispatch($job);
        dispatch(new TestJob2);
        dispatch($job)->onQueue('Q2');

        $this->assertEquals(2, Queue::size());
        $this->assertEquals(1, Queue::size('Q2'));
    }
}

class TestJob1 implements ShouldQueue
{
    use Queueable;
}

class TestJob2 implements ShouldQueue
{
    use Queueable;
}
