<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Attributes\Delay;
use Illuminate\Queue\Events\QueueFailedOver;
use Illuminate\Queue\FailoverQueue;
use Illuminate\Queue\QueueManager;
use Mockery;
use PHPUnit\Framework\TestCase;

class FailoverQueueTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setInstance(null);
    }

    public function test_push_fails_over_on_exception()
    {
        $queue = Mockery::mock(QueueManager::class);
        $events = new Dispatcher;
        $failedOver = [];
        $events->listen(QueueFailedOver::class, function ($event) use (&$failedOver) {
            $failedOver[] = $event;
        });
        $failover = new FailoverQueue($queue, $events, [
            'redis',
            'sync',
        ]);

        $redis = Mockery::mock(Queue::class);
        $queue->expects('connection')->with('redis')->andReturn($redis);

        $sync = Mockery::mock(Queue::class);
        $queue->expects('connection')->with('sync')->andReturn($sync);

        $redis->expects('push')->andReturnUsing(
            fn () => throw new \Exception('error')
        );

        $sync->expects('push');

        $failover->push('some-job');

        $this->assertCount(1, $failedOver);
        $this->assertSame('redis', $failedOver[0]->connectionName);
        $this->assertSame('some-job', $failedOver[0]->command);
        $this->assertSame('error', $failedOver[0]->exception->getMessage());
    }

    public function test_push_fails_over_on_exception_use_default_fallback_queue()
    {
        $queue = Mockery::mock(QueueManager::class);
        $events = Mockery::mock(Dispatcher::class);
        $failover = new FailoverQueue($queue, $events, [
            'redis',
            'database',
            'sync',
        ], 'default');

        $redis = Mockery::mock(Queue::class);
        $queue->expects('connection')->with('redis')->andReturn($redis);

        $database = Mockery::mock(Queue::class);
        $queue->expects('connection')->with('database')->andReturn($database);

        $events->expects('dispatch');

        $redis->expects('push')
            ->with('some-job', '', 'hello')
            ->andReturnUsing(
                fn () => throw new \Exception('error')
            );

        $database->expects('push')
            ->with('some-job', '', 'default');

        $failover->push('some-job', queue: 'hello');
    }

    public function test_push_raw_fails_over_on_exception_use_default_fallback_queue()
    {
        $queue = Mockery::mock(QueueManager::class);
        $events = Mockery::mock(Dispatcher::class);
        $failover = new FailoverQueue($queue, $events, [
            'redis',
            'database',
            'sync',
        ], 'default');

        $redis = Mockery::mock(Queue::class);
        $queue->expects('connection')->with('redis')->andReturn($redis);

        $database = Mockery::mock(Queue::class);
        $queue->expects('connection')->with('database')->andReturn($database);

        $events->expects('dispatch')->never();

        $redis->expects('pushRaw')
            ->with('pretty-raw-payload', 'raw')
            ->andReturnUsing(
                fn () => throw new \Exception('error')
            );

        $database->expects('pushRaw')
            ->with('pretty-raw-payload', 'default');

        $failover->pushRaw('pretty-raw-payload', queue: 'raw');
    }

    public function test_later_fails_over_on_exception_use_default_fallback_queue()
    {
        $queue = Mockery::mock(QueueManager::class);
        $events = Mockery::mock(Dispatcher::class);
        $failover = new FailoverQueue($queue, $events, [
            'redis',
            'database',
            'sync',
        ], 'default');

        $redis = Mockery::mock(Queue::class);
        $queue->expects('connection')->with('redis')->andReturn($redis);

        $database = Mockery::mock(Queue::class);
        $queue->expects('connection')->with('database')->andReturn($database);

        $events->expects('dispatch');

        $redis->expects('later')
            ->with(15, 'run-later', '', 'long')
            ->andReturnUsing(
                fn () => throw new \Exception('error')
            );

        $database->expects('later')
            ->with(15, 'run-later', '', 'default');

        $failover->later(15, 'run-later', queue: 'long');
    }

    public function test_bulk_respects_job_delays()
    {
        $queue = Mockery::mock(QueueManager::class);
        $failover = new FailoverQueue($queue, new Dispatcher, ['sync']);

        $sync = Mockery::mock(Queue::class);
        $queue->expects('connection')->times(3)->with('sync')->andReturn($sync);

        $sync->expects('later')->with(15, Mockery::type(FailoverJobWithDelayAttribute::class), '', null);
        $sync->expects('later')->with(30, Mockery::type(FailoverJobWithDelayProperty::class), '', null);
        $sync->expects('push')->with('regular-job', '', null);

        $failover->bulk([new FailoverJobWithDelayAttribute, new FailoverJobWithDelayProperty, 'regular-job']);
    }
}

#[Delay(15)]
class FailoverJobWithDelayAttribute
{
}

class FailoverJobWithDelayProperty
{
    public $delay = 30;
}
