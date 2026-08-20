<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Support\Facades\Queue;
use Illuminate\Tests\App\Jobs\QueueSizeJobOne;
use Illuminate\Tests\App\Jobs\QueueSizeJobTwo;
use Orchestra\Testbench\TestCase;

class QueueSizeTest extends TestCase
{
    public function test_queue_size()
    {
        Queue::fake();

        $this->assertEquals(0, Queue::size());
        $this->assertEquals(0, Queue::size('Q2'));

        $job = new QueueSizeJobOne;

        dispatch($job);
        dispatch(new QueueSizeJobTwo);
        dispatch($job)->onQueue('Q2');

        $this->assertEquals(2, Queue::size());
        $this->assertEquals(1, Queue::size('Q2'));
    }
}
