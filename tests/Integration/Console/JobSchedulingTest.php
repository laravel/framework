<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Bus\Queueable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class JobSchedulingTest extends TestCase
{
    public function testJobQueuingRespectsJobQueue()
    {
        Queue::fake();

        /** @var \Illuminate\Console\Scheduling\Schedule $scheduler */
        $scheduler = $this->app->make(Schedule::class);

        // all job names were set to an empty string so that the registered shutdown function in CallbackEvent does nothing
        // that function would in this test environment fire after everything was run, including the tearDown method
        // (which flushes the entire container) which would then result in a ReflectionException when the container would try
        // to resolve the config service (which is needed in order to resolve the cache store for the mutex that is being cleared)
        $scheduler->job(JobWithDefaultQueue::class)->name('')->everyMinute();
        $scheduler->job(JobWithDefaultQueueTwo::class, 'another-queue')->name('')->everyMinute();
        $scheduler->job(JobWithoutDefaultQueue::class)->name('')->everyMinute();

        $events = $scheduler->events();
        foreach ($events as $event) {
            $event->run($this->app);
        }

        Queue::assertPushedOn('test-queue', JobWithDefaultQueue::class);
        Queue::assertPushedOn('another-queue', JobWithDefaultQueueTwo::class);
        Queue::assertPushedOn(null, JobWithoutDefaultQueue::class);
        $this->assertTrue(Queue::pushed(JobWithDefaultQueueTwo::class, function ($job, $pushedQueue) {
            return $pushedQueue === 'test-queue-two';
        })->isEmpty());
    }

    public function testJobQueuingRespectsJobConnection()
    {
        Queue::fake();

        /** @var \Illuminate\Console\Scheduling\Schedule $scheduler */
        $scheduler = $this->app->make(Schedule::class);

        // all job names were set to an empty string so that the registered shutdown function in CallbackEvent does nothing
        // that function would in this test environment fire after everything was run, including the tearDown method
        // (which flushes the entire container) which would then result in a ReflectionException when the container would try
        // to resolve the config service (which is needed in order to resolve the cache store for the mutex that is being cleared)
        $scheduler->job(JobWithDefaultConnection::class)->name('')->everyMinute();
        $scheduler->job(JobWithDefaultConnection::class, null, 'foo')->name('')->everyMinute();
        $scheduler->job(JobWithoutDefaultConnection::class)->name('')->everyMinute();
        $scheduler->job(JobWithoutDefaultConnection::class, null, 'bar')->name('')->everyMinute();

        $events = $scheduler->events();
        foreach ($events as $event) {
            $event->run($this->app);
        }

        $this->assertSame(1, Queue::pushed(JobWithDefaultConnection::class, function (JobWithDefaultConnection $job, $pushedQueue) {
            return $job->connection === 'test-connection';
        })->count());

        $this->assertSame(1, Queue::pushed(JobWithDefaultConnection::class, function (JobWithDefaultConnection $job, $pushedQueue) {
            return $job->connection === 'foo';
        })->count());

        $this->assertSame(0, Queue::pushed(JobWithDefaultConnection::class, function (JobWithDefaultConnection $job, $pushedQueue) {
            return $job->connection === null;
        })->count());

        $this->assertSame(1, Queue::pushed(JobWithoutDefaultConnection::class, function (JobWithoutDefaultConnection $job, $pushedQueue) {
            return $job->connection === null;
        })->count());

        $this->assertSame(1, Queue::pushed(JobWithoutDefaultConnection::class, function (JobWithoutDefaultConnection $job, $pushedQueue) {
            return $job->connection === 'bar';
        })->count());
    }
}

class JobWithDefaultQueue implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    public function __construct()
    {
        $this->onQueue('test-queue');
    }
}

class JobWithDefaultQueueTwo implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    public function __construct()
    {
        $this->onQueue('test-queue-two');
    }
}

class JobWithoutDefaultQueue implements ShouldQueue
{
    use Queueable, InteractsWithQueue;
}

class JobWithDefaultConnection implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    public function __construct()
    {
        $this->onConnection('test-connection');
    }
}

class JobWithoutDefaultConnection implements ShouldQueue
{
    use Queueable, InteractsWithQueue;
}
