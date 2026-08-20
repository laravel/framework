<?php

namespace Illuminate\Tests\Queue;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\QueueableEntity;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Queue\SyncQueue;
use Illuminate\Tests\App\Jobs\FailingSyncQueueJob;
use Illuminate\Tests\App\Jobs\SyncQueueAfterCommitInterfaceJob;
use Illuminate\Tests\App\Jobs\SyncQueueAfterCommitInterfaceUniqueJob;
use Illuminate\Tests\App\Jobs\SyncQueueJob;
use LogicException;
use Mockery;
use PHPUnit\Framework\TestCase;

class QueueSyncQueueTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setInstance(null);
    }

    public function testPushShouldFireJobInstantly()
    {
        unset($_SERVER['__sync.test']);

        $sync = new SyncQueue;
        $container = new Container;
        $sync->setContainer($container);

        $sync->push(SyncQueueTestHandler::class, ['foo' => 'bar']);
        $this->assertInstanceOf(SyncJob::class, $_SERVER['__sync.test'][0]);
        $this->assertEquals(['foo' => 'bar'], $_SERVER['__sync.test'][1]);
    }

    public function testFailedJobGetsHandledWhenAnExceptionIsThrown()
    {
        unset($_SERVER['__sync.failed']);

        $sync = new SyncQueue;
        $container = new Container;
        Container::setInstance($container);
        $events = Mockery::mock(Dispatcher::class);
        $events->expects('dispatch')->times(4);
        $container->instance('events', $events);
        $container->instance(Dispatcher::class, $events);
        $sync->setContainer($container);

        try {
            $sync->push(FailingSyncQueueTestHandler::class, ['foo' => 'bar']);
        } catch (Exception) {
            $this->assertTrue($_SERVER['__sync.failed']);
        }

        Container::setInstance();
    }

    public function testFailedJobHasAccessToJobInstance()
    {
        unset($_SERVER['__sync.failed']);

        $sync = new SyncQueue;
        $container = new Container;
        $container->bind(\Illuminate\Contracts\Events\Dispatcher::class, \Illuminate\Events\Dispatcher::class);
        $container->bind(\Illuminate\Contracts\Bus\Dispatcher::class, \Illuminate\Bus\Dispatcher::class);
        $container->bind(\Illuminate\Contracts\Container\Container::class, \Illuminate\Container\Container::class);
        $sync->setContainer($container);

        SyncQueue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['data' => ['extra' => 'extraValue']];
        });

        try {
            $sync->push(new FailingSyncQueueJob());
        } catch (LogicException) {
            $this->assertSame('extraValue', $_SERVER['__sync.failed']);
        }
    }

    public function testCreatesPayloadObject()
    {
        $sync = new SyncQueue;
        $container = new Container;
        $container->bind(\Illuminate\Contracts\Events\Dispatcher::class, \Illuminate\Events\Dispatcher::class);
        $container->bind(\Illuminate\Contracts\Bus\Dispatcher::class, \Illuminate\Bus\Dispatcher::class);
        $container->bind(\Illuminate\Contracts\Container\Container::class, \Illuminate\Container\Container::class);
        $sync->setContainer($container);

        SyncQueue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['data' => ['extra' => 'extraValue']];
        });

        try {
            $sync->push(new SyncQueueJob());
        } catch (LogicException $e) {
            $this->assertSame('extraValue', $e->getMessage());
        }
    }

    public function testItAddsATransactionCallbackForAfterCommitJobs()
    {
        $sync = new SyncQueue;
        $container = new Container;
        $container->bind(\Illuminate\Contracts\Container\Container::class, \Illuminate\Container\Container::class);
        $transactionManager = Mockery::mock(DatabaseTransactionsManager::class);
        $transactionManager->expects('addCallback')->andReturn(null);
        $transactionManager->shouldNotReceive('addCallbackForRollback');
        $container->instance('db.transactions', $transactionManager);

        $sync->setContainer($container);
        $sync->push(new SyncQueueAfterCommitJob());
    }

    public function testItAddsATransactionCallbackForInterfaceBasedAfterCommitJobs()
    {
        $sync = new SyncQueue;
        $container = new Container;
        $container->bind(\Illuminate\Contracts\Container\Container::class, \Illuminate\Container\Container::class);
        $transactionManager = Mockery::mock(DatabaseTransactionsManager::class);
        $transactionManager->expects('addCallback')->andReturn(null);
        $transactionManager->shouldNotReceive('addCallbackForRollback');
        $container->instance('db.transactions', $transactionManager);

        $sync->setContainer($container);
        $sync->push(new SyncQueueAfterCommitInterfaceJob());
    }

    public function testItAddsATransactionCallbackForAfterCommitUniqueJobs()
    {
        $sync = new SyncQueue;
        $container = new Container;
        $container->bind(\Illuminate\Contracts\Container\Container::class, \Illuminate\Container\Container::class);
        $transactionManager = Mockery::mock(DatabaseTransactionsManager::class);
        $transactionManager->expects('addCallback')->andReturn(null);
        $transactionManager->expects('addCallbackForRollback')->andReturn(null);
        $container->instance('db.transactions', $transactionManager);

        $sync->setContainer($container);
        $sync->push(new SyncQueueAfterCommitUniqueJob());
    }

    public function testItAddsATransactionCallbackForInterfaceBasedAfterCommitUniqueJobs()
    {
        $sync = new SyncQueue;
        $container = new Container;
        $container->bind(\Illuminate\Contracts\Container\Container::class, \Illuminate\Container\Container::class);
        $transactionManager = Mockery::mock(DatabaseTransactionsManager::class);
        $transactionManager->expects('addCallback')->andReturn(null);
        $transactionManager->expects('addCallbackForRollback')->andReturn(null);
        $container->instance('db.transactions', $transactionManager);

        $sync->setContainer($container);
        $sync->push(new SyncQueueAfterCommitInterfaceUniqueJob());
    }
}

class SyncQueueTestEntity implements QueueableEntity
{
    public function getQueueableId()
    {
        return 1;
    }

    public function getQueueableConnection()
    {
        //
    }

    public function getQueueableRelations()
    {
        //
    }
}

class SyncQueueTestHandler
{
    public function fire($job, $data)
    {
        $_SERVER['__sync.test'] = func_get_args();
    }
}

class FailingSyncQueueTestHandler
{
    public function fire($job, $data)
    {
        throw new Exception;
    }

    public function failed()
    {
        $_SERVER['__sync.failed'] = true;
    }
}

class SyncQueueAfterCommitJob
{
    use InteractsWithQueue;

    public $afterCommit = true;

    public function handle()
    {
    }
}

class SyncQueueAfterCommitUniqueJob implements ShouldBeUnique
{
    use InteractsWithQueue;

    public $afterCommit = true;

    public function handle()
    {
    }
}
