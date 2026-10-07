<?php

namespace Illuminate\Tests\Queue;

use JMac\Testing\Matching\Argument;
use JMac\Testing\Double;
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
        $queue = Double::for(QueueManager::class);
        $events = new Dispatcher;
        $failedOver = [];
        $events->listen(QueueFailedOver::class, function ($event) use (&$failedOver) {
            $failedOver[] = $event;
        });
        $failover = new FailoverQueue($queue, $events, [
            'redis',
            'sync',
        ]);

        $redis = Double::for(Queue::class);
        $queue->expects('connection')->with('redis')->returns($redis);

        $sync = Double::for(Queue::class);
        $queue->expects('connection')->with('sync')->returns($sync);

        $redis->expects('push')->resolves(fn () => throw new \Exception('error'));

        $sync->expects('push');

        $failover->push('some-job');

        $this->assertCount(1, $failedOver);
        $this->assertSame('redis', $failedOver[0]->connectionName);
        $this->assertSame('some-job', $failedOver[0]->command);
        $this->assertSame('error', $failedOver[0]->exception->getMessage());
    }

    public function test_bulk_respects_job_delays()
    {
        $queue = Double::for(QueueManager::class);
        $failover = new FailoverQueue($queue, new Dispatcher, ['sync']);

        $sync = Double::for(Queue::class);
        $queue->expects('connection')->times(3)->with('sync')->returns($sync);

        $sync->expects('later')->with(15, Argument::type(FailoverJobWithDelayAttribute::class), '', null);
        $sync->expects('later')->with(30, Argument::type(FailoverJobWithDelayProperty::class), '', null);
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
