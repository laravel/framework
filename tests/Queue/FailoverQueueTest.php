<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Attributes\Delay;
use Illuminate\Queue\FailoverQueue;
use Illuminate\Queue\QueueManager;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class FailoverQueueTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setInstance(null);
    }

    public function test_push_fails_over_on_exception()
    {
        $queue = m::mock(QueueManager::class);
        $events = m::mock(Dispatcher::class);
        $failover = new FailoverQueue($queue, $events, [
            'redis',
            'sync',
        ]);

        $redis = m::mock('stdClass');
        $queue->expects('connection')->with('redis')->andReturn($redis);

        $sync = m::mock('stdClass');
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
        $queue = m::mock(QueueManager::class);
        $failover = new FailoverQueue($queue, m::mock(Dispatcher::class), ['sync']);

        $sync = m::mock('stdClass');
        $queue->expects('connection')->times(3)->with('sync')->andReturn($sync);

        $sync->expects('later')->with(15, m::type(FailoverJobWithDelayAttribute::class), '', null);
        $sync->expects('later')->with(30, m::type(FailoverJobWithDelayProperty::class), '', null);
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
