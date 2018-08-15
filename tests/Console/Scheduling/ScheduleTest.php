<?php

namespace Illuminate\Tests\Console\Scheduling;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Queue;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function setUp()
    {
        parent::setUp();

        $container = Container::getInstance();

        $container->instance('Illuminate\Console\Scheduling\EventMutex', m::mock('Illuminate\Console\Scheduling\CacheEventMutex'));

        $container->instance('Illuminate\Console\Scheduling\SchedulingMutex', m::mock('Illuminate\Console\Scheduling\CacheSchedulingMutex'));
    }

    public function testJob()
    {
        Queue::fake();

        $scheduler = new Schedule();

        // Create a new job.
        $scheduler->job(JobWithDefaultQueue::class)->everyMinute();
        $scheduler->job(JobWithoutDefaultQueue::class)->everyMinute();

        // Need to fire the event, but because it's async/queued, I have no idea how to mock/fire this.

        Queue::assertPushedOn('test-queue', JobWithDefaultQueue::class);
        Queue::assertPushedOn('default', JobWithoutDefaultQueue::class);
    }
}

class JobWithDefaultQueue implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use \Illuminate\Bus\Queueable,
        \Illuminate\Queue\InteractsWithQueue;

    public function __construct()
    {
        $this->onQueue('test-queue');
    }
}

class JobWithoutDefaultQueue implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use \Illuminate\Bus\Queueable,
        \Illuminate\Queue\InteractsWithQueue;
}
