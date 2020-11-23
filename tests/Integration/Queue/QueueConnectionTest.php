<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Bus;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class QueueConnectionTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');
        $app['config']->set('queue.default', 'sqs');
        $app['config']->set('queue.connections.sqs.after_commits', true);
    }

    protected function tearDown(): void
    {
        QueueConnectionTestJob::$ran = false;
        Connection::$totalTransactions;
        Connection::$afterTransactionCallbacks = [];
    }

    public function testJobWontGetDispatchedInsideATransaction()
    {
        Connection::$totalTransactions = 1;

        Bus::dispatch(new QueueConnectionTestJob);
        Bus::dispatch(new QueueConnectionTestJob);

        $this->assertFalse(QueueConnectionTestJob::$ran);
        $this->assertCount(2, Connection::$afterTransactionCallbacks);
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
