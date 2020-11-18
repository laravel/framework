<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
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
    }

    protected function tearDown(): void
    {
        QueueConnectionTestJob::$ran = false;
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
