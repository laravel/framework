<?php

namespace Illuminate\Tests\Queue;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Attributes\Delay;
use Illuminate\Queue\Connectors\FailoverConnector;
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
            fn () => throw new Exception('error')
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

    public function test_push_defers_after_commit_job_and_fails_over_after_commit()
    {
        $queue = Mockery::mock(QueueManager::class);
        $events = new Dispatcher;
        $failedOver = [];
        $events->listen(QueueFailedOver::class, function ($event) use (&$failedOver) {
            $failedOver[] = $event;
        });

        $failover = new FailoverQueue($queue, $events, [
            'redis',
            'database',
        ]);

        $container = new Container;
        $transactionsManager = new DatabaseTransactionsManager;
        $container->instance('db.transactions', $transactionsManager);
        $failover->setContainer($container);

        $redis = Mockery::mock(Queue::class);
        $database = Mockery::mock(Queue::class);
        $queue->shouldReceive('connection')->with('redis')->andReturn($redis);
        $queue->shouldReceive('connection')->with('database')->andReturn($database);

        $job = new FailoverAfterCommitJob;

        $pushedToDatabase = false;
        $redis->expects('push')->never();
        $database->expects('push')->never();

        // Start transaction
        $transactionsManager->begin('default', 1);

        $failover->push($job);

        // Job has not been pushed yet because transaction is active
        $this->assertEmpty($failedOver);

        // When commit runs, redis throws, database succeeds
        $redis->shouldReceive('push')->with($job)->once()->andReturnUsing(
            fn () => throw new Exception('Redis connection refused')
        );
        $database->shouldReceive('push')->with($job)->once()->andReturnUsing(function () use (&$pushedToDatabase) {
            $pushedToDatabase = true;

            return 123;
        });

        $transactionsManager->commit('default', 1, 0);

        $this->assertTrue($pushedToDatabase);
        $this->assertCount(1, $failedOver);
        $this->assertSame('redis', $failedOver[0]->connectionName);
        $this->assertSame($job, $failedOver[0]->command);
        $this->assertSame('Redis connection refused', $failedOver[0]->exception->getMessage());
    }

    public function test_later_defers_after_commit_job_and_fails_over_after_commit()
    {
        $queue = Mockery::mock(QueueManager::class);
        $events = new Dispatcher;
        $failedOver = [];
        $events->listen(QueueFailedOver::class, function ($event) use (&$failedOver) {
            $failedOver[] = $event;
        });

        $failover = new FailoverQueue($queue, $events, [
            'redis',
            'database',
        ]);

        $container = new Container;
        $transactionsManager = new DatabaseTransactionsManager;
        $container->instance('db.transactions', $transactionsManager);
        $failover->setContainer($container);

        $redis = Mockery::mock(Queue::class);
        $database = Mockery::mock(Queue::class);
        $queue->shouldReceive('connection')->with('redis')->andReturn($redis);
        $queue->shouldReceive('connection')->with('database')->andReturn($database);

        $job = new FailoverAfterCommitJob;

        $transactionsManager->begin('default', 1);

        $failover->later(60, $job);

        $this->assertEmpty($failedOver);

        $redis->shouldReceive('later')->with(60, $job)->once()->andReturnUsing(
            fn () => throw new Exception('Redis unavailable')
        );
        $database->shouldReceive('later')->with(60, $job)->once()->andReturn(1);

        $transactionsManager->commit('default', 1, 0);

        $this->assertCount(1, $failedOver);
        $this->assertSame('redis', $failedOver[0]->connectionName);
        $this->assertSame('Redis unavailable', $failedOver[0]->exception->getMessage());
    }

    public function test_after_commit_job_is_discarded_on_transaction_rollback()
    {
        $queue = Mockery::mock(QueueManager::class);
        $failover = new FailoverQueue($queue, new Dispatcher, ['redis', 'database']);

        $container = new Container;
        $transactionsManager = new DatabaseTransactionsManager;
        $container->instance('db.transactions', $transactionsManager);
        $failover->setContainer($container);

        $redis = Mockery::mock(Queue::class);
        $database = Mockery::mock(Queue::class);
        $queue->shouldReceive('connection')->with('redis')->andReturn($redis);
        $queue->shouldReceive('connection')->with('database')->andReturn($database);

        $job = new FailoverAfterCommitJob;

        $redis->shouldNotReceive('push');
        $database->shouldNotReceive('push');

        $transactionsManager->begin('default', 1);

        $failover->push($job);

        $transactionsManager->rollback('default', 0);
    }

    public function test_connection_level_after_commit_configuration_defers_push()
    {
        $queue = Mockery::mock(QueueManager::class);
        $failover = new FailoverQueue($queue, new Dispatcher, ['sync'], true);

        $container = new Container;
        $transactionsManager = new DatabaseTransactionsManager;
        $container->instance('db.transactions', $transactionsManager);
        $failover->setContainer($container);

        $sync = Mockery::mock(Queue::class);
        $queue->shouldReceive('connection')->with('sync')->andReturn($sync);

        $transactionsManager->begin('default', 1);

        $pushed = false;
        $sync->expects('push')->never();

        $failover->push('regular-job');

        $sync->shouldReceive('push')->with('regular-job')->once()->andReturnUsing(function () use (&$pushed) {
            $pushed = true;

            return 1;
        });

        $transactionsManager->commit('default', 1, 0);

        $this->assertTrue($pushed);
    }

    public function test_failover_connector_passes_after_commit_configuration()
    {
        $queueManager = Mockery::mock(QueueManager::class);
        $events = new Dispatcher;
        $connector = new FailoverConnector($queueManager, $events);

        $failover = $connector->connect([
            'connections' => ['redis', 'sync'],
            'after_commit' => true,
        ]);

        $this->assertInstanceOf(FailoverQueue::class, $failover);

        $ref = new \ReflectionProperty($failover, 'dispatchAfterCommit');
        $this->assertTrue($ref->getValue($failover));
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
