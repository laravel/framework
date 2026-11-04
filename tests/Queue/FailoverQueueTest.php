<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Attributes\Delay;
use Illuminate\Queue\Events\QueueFailedOver;
use Illuminate\Queue\FailoverQueue;
use Illuminate\Queue\Queue as BaseQueue;
use Illuminate\Queue\QueueManager;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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

    public function test_after_commit_job_fails_over_when_the_transaction_commits()
    {
        $job = new FailoverAfterCommitJob;
        $pushedToSync = false;

        $redis = Mockery::mock(Queue::class);
        $redis->expects('push')->with($job)->andThrow(new RuntimeException('Connection refused'));

        $sync = Mockery::mock(Queue::class);
        $sync->expects('push')->with($job)->andReturnUsing(function () use (&$pushedToSync) {
            $pushedToSync = true;
        });

        [$failover, $transactions, $failedOver] = $this->failoverQueueWithTransactions(['redis' => $redis, 'sync' => $sync]);

        $transactions->begin('default', 1);

        $failover->push($job);

        $this->assertFalse($pushedToSync);
        $this->assertCount(0, $failedOver);

        $transactions->commit('default', 1, 0);

        $this->assertTrue($pushedToSync);
        $this->assertCount(1, $failedOver);
        $this->assertSame('redis', $failedOver[0]->connectionName);
        $this->assertSame($job, $failedOver[0]->command);
    }

    public function test_after_commit_job_delayed_with_later_fails_over_when_the_transaction_commits()
    {
        $job = new FailoverAfterCommitJob;
        $delayedOnSync = false;

        $redis = Mockery::mock(Queue::class);
        $redis->expects('later')->with(10, $job)->andThrow(new RuntimeException('Connection refused'));

        $sync = Mockery::mock(Queue::class);
        $sync->expects('later')->with(10, $job)->andReturnUsing(function () use (&$delayedOnSync) {
            $delayedOnSync = true;
        });

        [$failover, $transactions, $failedOver] = $this->failoverQueueWithTransactions(['redis' => $redis, 'sync' => $sync]);

        $transactions->begin('default', 1);
        $failover->later(10, $job);

        $this->assertFalse($delayedOnSync);

        $transactions->commit('default', 1, 0);

        $this->assertTrue($delayedOnSync);
        $this->assertCount(1, $failedOver);
    }

    public function test_after_commit_job_is_not_queued_when_the_transaction_rolls_back()
    {
        $pushed = false;

        $redis = Mockery::mock(Queue::class);
        $redis->allows('push')->andReturnUsing(function () use (&$pushed) {
            $pushed = true;
        });

        [$failover, $transactions, $failedOver] = $this->failoverQueueWithTransactions(['redis' => $redis]);

        $transactions->begin('default', 1);
        $failover->push(new FailoverAfterCommitJob);
        $transactions->rollback('default', 0);

        $this->assertFalse($pushed);
        $this->assertCount(0, $failedOver);
    }

    public function test_job_fails_over_when_an_inner_connection_is_configured_to_dispatch_after_commit()
    {
        $job = new FailoverPlainJob;
        $pushedToSync = false;

        $redis = new FailoverUnavailableAfterCommitQueue;

        $sync = Mockery::mock(Queue::class);
        $sync->expects('push')->with($job)->andReturnUsing(function () use (&$pushedToSync) {
            $pushedToSync = true;
        });

        [$failover, $transactions, $failedOver] = $this->failoverQueueWithTransactions(['redis' => $redis, 'sync' => $sync]);

        $transactions->begin('default', 1);
        $failover->push($job);

        $this->assertFalse($pushedToSync);

        $transactions->commit('default', 1, 0);

        $this->assertTrue($pushedToSync);
        $this->assertCount(1, $failedOver);
        $this->assertSame('redis', $failedOver[0]->connectionName);
        $this->assertSame('Connection refused', $failedOver[0]->exception->getMessage());
    }

    public function test_job_that_opts_out_of_after_commit_is_pushed_inside_the_transaction()
    {
        $job = new FailoverAfterCommitJob;
        $job->afterCommit = false;

        $pushed = false;

        $redis = Mockery::mock(Queue::class);
        $redis->expects('push')->with($job)->andReturnUsing(function () use (&$pushed) {
            $pushed = true;
        });

        [$failover, $transactions] = $this->failoverQueueWithTransactions(['redis' => $redis]);

        $transactions->begin('default', 1);
        $failover->push($job);

        $this->assertTrue($pushed);
    }

    public function test_job_is_pushed_inside_the_transaction_when_only_a_secondary_connection_dispatches_after_commit()
    {
        $job = new FailoverPlainJob;
        $pushed = false;

        $redis = Mockery::mock(Queue::class);
        $redis->expects('push')->with($job)->andReturnUsing(function () use (&$pushed) {
            $pushed = true;
        });

        [$failover, $transactions] = $this->failoverQueueWithTransactions([
            'redis' => $redis, 'sync' => new FailoverUnavailableAfterCommitQueue,
        ]);

        $transactions->begin('default', 1);
        $failover->push($job);

        $this->assertTrue($pushed);
    }

    public function test_after_commit_job_fails_over_once_transactions_on_every_connection_have_committed()
    {
        $job = new FailoverAfterCommitJob;
        $pushedToSync = false;

        $sync = Mockery::mock(Queue::class);
        $sync->expects('push')->with($job)->andReturnUsing(function () use (&$pushedToSync) {
            $pushedToSync = true;
        });

        [$failover, $transactions, $failedOver] = $this->failoverQueueWithTransactions([
            'redis' => new FailoverUnavailableAfterCommitQueue, 'sync' => $sync,
        ]);

        $transactions->begin('mysql', 1);
        $transactions->begin('pgsql', 1);
        $failover->push($job);
        $transactions->commit('pgsql', 1, 0);

        $this->assertFalse($pushedToSync);
        $this->assertCount(0, $failedOver);

        $transactions->commit('mysql', 1, 0);

        $this->assertTrue($pushedToSync);
        $this->assertCount(1, $failedOver);
        $this->assertSame('redis', $failedOver[0]->connectionName);
    }

    public function test_bulk_after_commit_jobs_are_pushed_when_the_transaction_commits()
    {
        $jobs = [new FailoverAfterCommitJob, new FailoverAfterCommitJob];
        $pushed = 0;

        $redis = Mockery::mock(Queue::class);
        $redis->expects('push')->twice()->andReturnUsing(function () use (&$pushed) {
            $pushed++;
        });

        [$failover, $transactions] = $this->failoverQueueWithTransactions(['redis' => $redis]);

        $transactions->begin('default', 1);
        $failover->bulk($jobs);

        $this->assertSame(0, $pushed);

        $transactions->commit('default', 1, 0);

        $this->assertSame(2, $pushed);
    }

    public function test_unique_lock_is_released_when_the_transaction_rolls_back()
    {
        $redis = Mockery::mock(Queue::class);
        $redis->expects('push')->never();

        [$failover, $transactions, , $container] = $this->failoverQueueWithTransactions(['redis' => $redis]);

        $released = $this->expectUniqueLockRelease($container);

        $transactions->begin('default', 1);
        $failover->push(new FailoverUniqueAfterCommitJob);
        $transactions->rollback('default', 0);

        $this->assertSame(1, $released->count);
    }

    public function test_unique_lock_is_released_when_a_transaction_on_another_connection_rolls_back_after_the_first_commits()
    {
        $redis = Mockery::mock(Queue::class);
        $redis->expects('push')->never();

        [$failover, $transactions, , $container] = $this->failoverQueueWithTransactions(['redis' => $redis]);

        $released = $this->expectUniqueLockRelease($container);

        $transactions->begin('mysql', 1);
        $transactions->begin('pgsql', 1);
        $failover->push(new FailoverUniqueAfterCommitJob);
        $transactions->commit('pgsql', 1, 0);

        $this->assertSame(0, $released->count);

        $transactions->rollback('mysql', 0);

        $this->assertSame(1, $released->count);
    }

    protected function expectUniqueLockRelease(Container $container): object
    {
        $released = (object) ['count' => 0];

        $lock = Mockery::mock(Lock::class);
        $lock->allows('forceRelease')->andReturnUsing(function () use ($released) {
            $released->count++;
        });

        $cache = Mockery::mock(CacheRepository::class);
        $cache->allows('lock')->andReturn($lock);
        $container->instance(CacheRepository::class, $cache);

        return $released;
    }

    /**
     * @param  array<string, object>  $connections
     * @return array{0: FailoverQueue, 1: DatabaseTransactionsManager, 2: \ArrayObject, 3: Container}
     */
    protected function failoverQueueWithTransactions(array $connections): array
    {
        $container = new Container;
        $container->instance('db.transactions', $transactions = new DatabaseTransactionsManager);

        $events = new Dispatcher($container);
        $failedOver = new \ArrayObject;
        $events->listen(QueueFailedOver::class, fn ($event) => $failedOver[] = $event);

        $manager = Mockery::mock(QueueManager::class);

        foreach ($connections as $name => $queue) {
            if ($queue instanceof BaseQueue) {
                $queue->setContainer($container);
            }

            $manager->allows('connection')->with($name)->andReturn($queue);
        }

        $failover = new FailoverQueue($manager, $events, array_keys($connections));
        $failover->setContainer($container);

        return [$failover, $transactions, $failedOver, $container];
    }
}

class FailoverAfterCommitJob implements ShouldQueueAfterCommit
{
    public $afterCommit;
}

class FailoverUniqueAfterCommitJob implements ShouldBeUnique, ShouldQueueAfterCommit
{
}

class FailoverPlainJob
{
}

class FailoverUnavailableAfterCommitQueue extends BaseQueue
{
    public function __construct()
    {
        $this->dispatchAfterCommit = true;
    }

    public function push($job, $data = '', $queue = null)
    {
        return $this->enqueueUsing($job, '', $queue, null, function () {
            throw new RuntimeException('Connection refused');
        });
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
