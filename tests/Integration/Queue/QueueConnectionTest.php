<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Bus;
use Mockery as m;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;
use Throwable;

#[WithConfig('queue.default', 'sqs')]
#[WithConfig('queue.connections.sqs.after_commit', true)]
class QueueConnectionTest extends TestCase
{
    protected function tearDown(): void
    {
        QueueConnectionTestJob::$ran = false;
        QueueConnectionTestUniqueJob::$ran = false;

        parent::tearDown();
    }

    public function testJobWontGetDispatchedInsideATransaction()
    {
        $this->app->singleton('db.transactions', function () {
            $transactionManager = m::mock(DatabaseTransactionsManager::class);
            $transactionManager->shouldReceive('addCallback')->once()->andReturn(null);
            $transactionManager->shouldNotReceive('addCallbackForRollback');

            return $transactionManager;
        });

        Bus::dispatch(new QueueConnectionTestJob);
    }

    public function testJobWillGetDispatchedInsideATransactionWhenExplicitlyIndicated()
    {
        $this->app->singleton('db.transactions', function () {
            $transactionManager = m::mock(DatabaseTransactionsManager::class);
            $transactionManager->shouldNotReceive('addCallback')->andReturn(null);
            $transactionManager->shouldNotReceive('addCallbackForRollback');

            return $transactionManager;
        });

        try {
            Bus::dispatch((new QueueConnectionTestJob)->beforeCommit());
        } catch (Throwable) {
            // This job was dispatched
        }
    }

    public function testJobWontGetDispatchedInsideATransactionWhenExplicitlyIndicated()
    {
        $this->app['config']->set('queue.connections.sqs.after_commit', false);

        $this->app->singleton('db.transactions', function () {
            $transactionManager = m::mock(DatabaseTransactionsManager::class);
            $transactionManager->shouldReceive('addCallback')->once()->andReturn(null);
            $transactionManager->shouldNotReceive('addCallbackForRollback');

            return $transactionManager;
        });

        try {
            Bus::dispatch((new QueueConnectionTestJob)->afterCommit());
        } catch (SqsException) {
            // This job was dispatched
        }
    }

    public function testUniqueJobWontGetDispatchedInsideATransaction()
    {
        $this->app->singleton('db.transactions', function () {
            $transactionManager = m::mock(DatabaseTransactionsManager::class);
            $transactionManager->shouldReceive('addCallback')->once()->andReturn(null);
            $transactionManager->shouldReceive('addCallbackForRollback')->once()->andReturn(null);

            return $transactionManager;
        });

        Bus::dispatch(new QueueConnectionTestUniqueJob);
    }

    public function testUniqueJobWillGetDispatchedInsideATransactionWhenExplicitlyIndicated()
    {
        $this->app->singleton('db.transactions', function () {
            $transactionManager = m::mock(DatabaseTransactionsManager::class);
            $transactionManager->shouldNotReceive('addCallback')->andReturn(null);
            $transactionManager->shouldNotReceive('addCallbackForRollback')->andReturn(null);

            return $transactionManager;
        });

        try {
            Bus::dispatch((new QueueConnectionTestUniqueJob)->beforeCommit());
        } catch (Throwable) {
            // This job was dispatched
        }
    }

    public function testUniqueJobWontGetDispatchedInsideATransactionWhenExplicitlyIndicated()
    {
        $this->app['config']->set('queue.connections.sqs.after_commit', false);

        $this->app->singleton('db.transactions', function () {
            $transactionManager = m::mock(DatabaseTransactionsManager::class);
            $transactionManager->shouldReceive('addCallback')->once()->andReturn(null);
            $transactionManager->shouldReceive('addCallbackForRollback')->once()->andReturn(null);

            return $transactionManager;
        });

        try {
            Bus::dispatch((new QueueConnectionTestUniqueJob)->afterCommit());
        } catch (SqsException) {
            // This job was dispatched
        }
    }
}

class QueueConnectionTestJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
    }
}

class QueueConnectionTestUniqueJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
    }
}
