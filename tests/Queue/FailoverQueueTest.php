<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\FailoverQueue;
use Illuminate\Queue\QueueManager;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class FailoverQueueTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        Container::setInstance(null);
    }

    public function test_push_fails_over_on_exception()
    {
        $failover = new FailoverQueue($queue = m::mock(QueueManager::class), $events = m::mock(Dispatcher::class), [
            'redis',
            'sync',
        ]);

        $queue->shouldReceive('connection')->once()->with('redis')->andReturn(
            $redis = m::mock('stdClass'),
        );

        $queue->shouldReceive('connection')->once()->with('sync')->andReturn(
            $sync = m::mock('stdClass'),
        );

        $events->shouldReceive('dispatch')->once();

        $redis->shouldReceive('push')->once()->andReturnUsing(
            fn () => throw new \Exception('error')
        );

        $sync->shouldReceive('push')->once();

        $failover->push('some-job');
    }
}
