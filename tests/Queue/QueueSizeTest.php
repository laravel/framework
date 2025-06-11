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

    public function test_driver_methods_exist_and_return_expected_defaults()
    {
        Queue::fake();

        $queue = Queue::connection();

        $job = new TestJob1;

        dispatch($job);
        dispatch($job);
        dispatch(new TestJob2)->delay(5);
        dispatch($job)->onQueue('Q2');

        $this->assertEquals(2, $queue->sizePending());
        $this->assertEquals(1, $queue->sizeDelayed());
        $this->assertEquals(0, $queue->sizeReserved());
        $this->assertIsInt($queue->oldestPending());

        $this->assertEquals(1, $queue->sizePending('Q2'));
        $this->assertEquals(0, $queue->sizeDelayed('Q2'));
        $this->assertEquals(0, $queue->sizeReserved('Q2'));
        $this->assertIsInt($queue->oldestPending('Q2'));

        $queue->process();
        $queue->process('Q2');

        $this->assertEquals(1, $queue->sizePending());
        $this->assertEquals(1, $queue->sizeDelayed());
        $this->assertEquals(1, $queue->sizeReserved());

        $this->assertEquals(0, $queue->sizePending('Q2'));
        $this->assertEquals(0, $queue->sizeDelayed('Q2'));
        $this->assertEquals(1, $queue->sizeReserved('Q2'));
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
