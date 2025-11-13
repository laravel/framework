<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Events\QueueDepthExceeded;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\Worker;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class QueueDepthNotificationTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testDispatchesEventWhenQueueDepthExceedsThreshold()
    {
        $container = new Container;
        $manager = m::mock(QueueManager::class);
        $events = m::mock(Dispatcher::class);
        $exceptionHandler = m::mock(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $cache = new Repository(new ArrayStore);

        $worker = new Worker($manager, $events, $exceptionHandler, fn () => false);
        $worker->setCache($cache);

        // Mock connection and queue
        $connection = m::mock(stdClass::class);
        $connection->shouldReceive('size')->with('default')->andReturn(150);
        $manager->shouldReceive('connection')->with('sync')->andReturn($connection);

        // Mock job with maxPendingJobs = 100
        $job = m::mock(JobContract::class);
        $job->shouldReceive('maxPendingJobs')->andReturn(100);
        $job->shouldReceive('getQueue')->andReturn('default');
        $job->shouldReceive('isDeleted')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('maxTries')->andReturn(null);
        $job->shouldReceive('retryUntil')->andReturn(null);
        $job->shouldReceive('attempts')->andReturn(1);

        // Expect QueueDepthExceeded event to be dispatched
        $events->shouldReceive('dispatch')->once()->withArgs(function ($event) {
            return $event instanceof QueueDepthExceeded
                && $event->connection === 'sync'
                && $event->queue === 'default'
                && $event->size === 150
                && $event->threshold === 100;
        });

        // Trigger the check by calling the protected method via reflection
        $reflection = new \ReflectionClass($worker);
        $method = $reflection->getMethod('checkQueueDepthAndNotify');
        $method->setAccessible(true);
        $method->invoke($worker, 'sync', $job);
    }

    public function testDoesNotDispatchEventWhenBelowThreshold()
    {
        $container = new Container;
        $manager = m::mock(QueueManager::class);
        $events = m::mock(Dispatcher::class);
        $exceptionHandler = m::mock(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $cache = new Repository(new ArrayStore);

        $worker = new Worker($manager, $events, $exceptionHandler, fn () => false);
        $worker->setCache($cache);

        // Mock connection with queue size below threshold
        $connection = m::mock(stdClass::class);
        $connection->shouldReceive('size')->with('default')->andReturn(50);
        $manager->shouldReceive('connection')->with('sync')->andReturn($connection);

        // Mock job with maxPendingJobs = 100
        $job = m::mock(JobContract::class);
        $job->shouldReceive('maxPendingJobs')->andReturn(100);
        $job->shouldReceive('getQueue')->andReturn('default');

        // Event should NOT be dispatched
        $events->shouldReceive('dispatch')->never();

        // Trigger the check
        $reflection = new \ReflectionClass($worker);
        $method = $reflection->getMethod('checkQueueDepthAndNotify');
        $method->setAccessible(true);
        $method->invoke($worker, 'sync', $job);
    }

    public function testDoesNotDispatchWhenMaxPendingJobsIsZero()
    {
        $container = new Container;
        $manager = m::mock(QueueManager::class);
        $events = m::mock(Dispatcher::class);
        $exceptionHandler = m::mock(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $cache = new Repository(new ArrayStore);

        $worker = new Worker($manager, $events, $exceptionHandler, fn () => false);
        $worker->setCache($cache);

        // Mock job with maxPendingJobs = 0 (disabled)
        $job = m::mock(JobContract::class);
        $job->shouldReceive('maxPendingJobs')->andReturn(0);

        // Event should NOT be dispatched
        $events->shouldReceive('dispatch')->never();

        // Trigger the check
        $reflection = new \ReflectionClass($worker);
        $method = $reflection->getMethod('checkQueueDepthAndNotify');
        $method->setAccessible(true);
        $method->invoke($worker, 'sync', $job);
    }

    public function testThrottlesRepeatedNotifications()
    {
        $container = new Container;
        $manager = m::mock(QueueManager::class);
        $events = m::mock(Dispatcher::class);
        $exceptionHandler = m::mock(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $cache = new Repository(new ArrayStore);

        $worker = new Worker($manager, $events, $exceptionHandler, fn () => false);
        $worker->setCache($cache);

        // Mock connection with high queue size
        $connection = m::mock(stdClass::class);
        $connection->shouldReceive('size')->with('default')->andReturn(150);
        $manager->shouldReceive('connection')->with('sync')->andReturn($connection);

        // Mock job
        $job = m::mock(JobContract::class);
        $job->shouldReceive('maxPendingJobs')->andReturn(100);
        $job->shouldReceive('getQueue')->andReturn('default');

        // Expect event only once
        $events->shouldReceive('dispatch')->once()->withArgs(function ($event) {
            return $event instanceof QueueDepthExceeded;
        });

        // Trigger the check twice
        $reflection = new \ReflectionClass($worker);
        $method = $reflection->getMethod('checkQueueDepthAndNotify');
        $method->setAccessible(true);
        $method->invoke($worker, 'sync', $job);
        $method->invoke($worker, 'sync', $job); // Should not dispatch again
    }
}
