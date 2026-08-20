<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Support\Facades\Bus;
use Illuminate\Tests\App\Jobs\ConnectionJob;
use Illuminate\Tests\App\Jobs\ConnectionUniqueJob;
use Mockery;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;
use Throwable;

#[WithConfig('queue.default', 'sqs')]
#[WithConfig('queue.connections.sqs.after_commit', true)]
class QueueConnectionTest extends TestCase
{
    protected function tearDown(): void
    {
        ConnectionJob::$ran = false;
        ConnectionUniqueJob::$ran = false;

        parent::tearDown();
    }

    public function testJobWontGetDispatchedInsideATransaction()
    {
        $this->app->singleton('db.transactions', function () {
            $transactionManager = Mockery::mock(DatabaseTransactionsManager::class);
            $transactionManager->expects('addCallback')->andReturn(null);
            $transactionManager->shouldNotReceive('addCallbackForRollback');

            return $transactionManager;
        });

        Bus::dispatch(new ConnectionJob);
    }

    public function testJobWillGetDispatchedInsideATransactionWhenExplicitlyIndicated()
    {
        $this->app->singleton('db.transactions', function () {
            $transactionManager = Mockery::mock(DatabaseTransactionsManager::class);
            $transactionManager->shouldNotReceive('addCallback')->andReturn(null);
            $transactionManager->shouldNotReceive('addCallbackForRollback');

            return $transactionManager;
        });

        try {
            Bus::dispatch((new ConnectionJob)->beforeCommit());
        } catch (Throwable) {
            // This job was dispatched
        }
    }

    public function testJobWontGetDispatchedInsideATransactionWhenExplicitlyIndicated()
    {
        $this->app['config']->set('queue.connections.sqs.after_commit', false);

        $this->app->singleton('db.transactions', function () {
            $transactionManager = Mockery::mock(DatabaseTransactionsManager::class);
            $transactionManager->expects('addCallback')->andReturn(null);
            $transactionManager->shouldNotReceive('addCallbackForRollback');

            return $transactionManager;
        });

        try {
            Bus::dispatch((new ConnectionJob)->afterCommit());
        } catch (SqsException) {
            // This job was dispatched
        }
    }

    public function testUniqueJobWontGetDispatchedInsideATransaction()
    {
        $this->app->singleton('db.transactions', function () {
            $transactionManager = Mockery::mock(DatabaseTransactionsManager::class);
            $transactionManager->expects('addCallback')->andReturn(null);
            $transactionManager->expects('addCallbackForRollback')->andReturn(null);

            return $transactionManager;
        });

        Bus::dispatch(new ConnectionUniqueJob);
    }

    public function testUniqueJobWillGetDispatchedInsideATransactionWhenExplicitlyIndicated()
    {
        $this->app->singleton('db.transactions', function () {
            $transactionManager = Mockery::mock(DatabaseTransactionsManager::class);
            $transactionManager->shouldNotReceive('addCallback')->andReturn(null);
            $transactionManager->shouldNotReceive('addCallbackForRollback')->andReturn(null);

            return $transactionManager;
        });

        try {
            Bus::dispatch((new ConnectionUniqueJob)->beforeCommit());
        } catch (Throwable) {
            // This job was dispatched
        }
    }

    public function testUniqueJobWontGetDispatchedInsideATransactionWhenExplicitlyIndicated()
    {
        $this->app['config']->set('queue.connections.sqs.after_commit', false);

        $this->app->singleton('db.transactions', function () {
            $transactionManager = Mockery::mock(DatabaseTransactionsManager::class);
            $transactionManager->expects('addCallback')->andReturn(null);
            $transactionManager->expects('addCallbackForRollback')->andReturn(null);

            return $transactionManager;
        });

        try {
            Bus::dispatch((new ConnectionUniqueJob)->afterCommit());
        } catch (SqsException) {
            // This job was dispatched
        }
    }
}
