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
        $queue->shouldReceive('connection')->once()->with('redis')->andReturn($redis);

        $sync = m::mock('stdClass');
        $queue->shouldReceive('connection')->once()->with('sync')->andReturn($sync);

        $events->shouldReceive('dispatch')->once();

        $redis->shouldReceive('push')->once()->andReturnUsing(
            fn () => throw new \Exception('error')
        );

        $sync->shouldReceive('push')->once();

        $failover->push('some-job');
    }

    public function test_bulk_respects_job_delays()
    {
        $queue = m::mock(QueueManager::class);
        $failover = new FailoverQueue($queue, m::mock(Dispatcher::class), ['sync']);

        $sync = m::mock('stdClass');
        $queue->shouldReceive('connection')->times(3)->with('sync')->andReturn($sync);

        $sync->shouldReceive('later')->once()->with(15, m::type(FailoverJobWithDelayAttribute::class), '', null);
        $sync->shouldReceive('later')->once()->with(30, m::type(FailoverJobWithDelayProperty::class), '', null);
        $sync->shouldReceive('push')->once()->with('regular-job', '', null);

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
