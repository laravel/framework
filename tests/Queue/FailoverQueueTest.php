<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Attributes\Delay;
use Illuminate\Queue\FailoverQueue;
use Illuminate\Queue\QueueManager;
use Mockery;
use PHPUnit\Framework\TestCase;

class FailoverQueueTest extends TestCase
{
    public function test_push_fails_over_on_exception()
    {
        $queue = Mockery::mock(QueueManager::class);
        $events = Mockery::mock(Dispatcher::class);
        $failover = new FailoverQueue($queue, $events, [
            'redis',
            'sync',
        ]);

        $redis = Mockery::mock('stdClass');
        $queue->expects('connection')->with('redis')->andReturn($redis);

        $sync = Mockery::mock('stdClass');
        $queue->expects('connection')->with('sync')->andReturn($sync);

        $events->expects('dispatch');

        $redis->expects('push')->andReturnUsing(
            fn () => throw new \Exception('error')
        );

        $sync->expects('push');

        $failover->push('some-job');
    }

    public function test_bulk_respects_job_delays()
    {
        $queue = Mockery::mock(QueueManager::class);
        $failover = new FailoverQueue($queue, Mockery::mock(Dispatcher::class), ['sync']);

        $sync = Mockery::mock('stdClass');
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
