<?php

namespace Illuminate\Tests\Queue;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Attributes\Delay;
use Illuminate\Queue\Events\QueueFailedOver;
use Illuminate\Queue\FailoverQueue;
use Illuminate\Queue\Queue as QueueBase;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Collection;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use PHPUnit\Framework\TestCase;

class FailoverQueueTest extends TestCase
{
    use VerifiesDoubles;

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

    public function test_after_commit_job_fails_over_when_the_transaction_commits()
    {
        [$failover, $queue, $transactions] = $this->failoverInsideTransaction();
        $failedOver = [];
        $failover->events->listen(QueueFailedOver::class, function ($event) use (&$failedOver) {
            $failedOver[] = $event;
        });

        $redis = Double::for(Queue::class);
        $sync = Double::for(Queue::class);
        $queue->expects('connection')->with('redis')->returns($redis);
        $queue->expects('connection')->with('sync')->returns($sync);
        $redis->expects('push')->resolves(fn () => throw new Exception('down'));
        $sync->expects('push');

        $this->assertNull($failover->push(new FailoverAfterCommitJob));
        $this->assertCount(1, $transactions->callbacks);
        $this->assertSame([], $failedOver);

        $transactions->pending = new Collection;
        $transactions->callbacks[0]();

        $this->assertCount(1, $failedOver);
        $this->assertSame('redis', $failedOver[0]->connectionName);
    }

    public function test_after_commit_job_pushes_immediately_when_no_transaction_is_open()
    {
        [$failover, $queue, $transactions] = $this->failoverInsideTransaction();
        $transactions->pending = new Collection;

        $redis = Double::for(Queue::class);
        $sync = Double::for(Queue::class);
        $queue->expects('connection')->with('redis')->returns($redis);
        $queue->expects('connection')->with('sync')->returns($sync);
        $redis->expects('push')->resolves(fn () => throw new Exception('down'));
        $sync->expects('push');

        $failover->push(new FailoverAfterCommitJob);

        $this->assertSame([], $transactions->callbacks);
    }

    public function test_primary_after_commit_connection_fails_over_after_the_transaction_commits()
    {
        $transactions = new RecordingTransactions;
        $container = new Container;
        $container->instance('db.transactions', $transactions);

        $primary = new ThrowingAfterCommitQueue;
        $sync = Double::for(Queue::class);
        $sync->expects('push');

        $queue = Double::for(QueueManager::class);
        $queue->expects('connection')->times(2)->with('redis')->returns($primary);
        $queue->expects('connection')->with('sync')->returns($sync);

        $failover = new FailoverQueue($queue, new Dispatcher, ['redis', 'sync']);
        $failover->setContainer($container);

        $this->assertNull($failover->push('plain-job'));
        $this->assertFalse($primary->pushed);

        $transactions->pending = new Collection;
        $transactions->callbacks[0]();

        $this->assertTrue($primary->pushed);
    }

    public function test_after_commit_failover_waits_until_every_connection_has_committed()
    {
        [$failover, $queue, $transactions] = $this->failoverInsideTransaction(openTransactions: 2);

        $redis = Double::for(Queue::class);
        $redis->expects('push')->resolves(fn () => throw new Exception('down'));
        $sync = Double::for(Queue::class);
        $sync->expects('push');
        $queue->expects('connection')->with('redis')->returns($redis);
        $queue->expects('connection')->with('sync')->returns($sync);

        $failover->push(new FailoverAfterCommitJob);

        $transactions->pending = new Collection([new \stdClass]);
        $transactions->callbacks[0]();

        $this->assertCount(2, $transactions->callbacks);

        $transactions->pending = new Collection;
        $transactions->callbacks[1]();
    }

    /**
     * @return array{0: \Illuminate\Queue\FailoverQueue, 1: \Illuminate\Queue\QueueManager, 2: \Illuminate\Tests\Queue\RecordingTransactions}
     */
    protected function failoverInsideTransaction(int $openTransactions = 1)
    {
        $transactions = new RecordingTransactions($openTransactions);
        $container = new Container;
        $container->instance('db.transactions', $transactions);

        $queue = Double::for(QueueManager::class);
        $events = new Dispatcher;
        $failover = new FailoverQueue($queue, $events, ['redis', 'sync']);
        $failover->setContainer($container);

        return [$failover, $queue, $transactions];
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

class FailoverAfterCommitJob implements ShouldQueueAfterCommit
{
}

class RecordingTransactions
{
    public $pending;

    public $callbacks = [];

    public function __construct(int $openTransactions = 1)
    {
        $this->pending = new Collection(array_fill(0, $openTransactions, new \stdClass));
    }

    public function callbackApplicableTransactions()
    {
        return $this->pending;
    }

    public function addCallback($callback)
    {
        $this->callbacks[] = $callback;
    }
}

class ThrowingAfterCommitQueue extends QueueBase
{
    public $pushed = false;

    public function __construct()
    {
        $this->dispatchAfterCommit = true;
    }

    public function push($job, $data = '', $queue = null)
    {
        $this->pushed = true;

        throw new Exception('down');
    }
}
