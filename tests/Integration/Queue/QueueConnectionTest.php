<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Mockery as m;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;
use Throwable;

#[WithMigration('queue')]
class QueueConnectionTest extends TestCase
{
    use DatabaseMigrations;

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('queue.default', 'sqs');
        $app['config']->set('queue.connections.sqs.after_commit', true);
    }

    protected function tearDown(): void
    {
        QueueConnectionTestJob::$ran = false;

        m::close();
    }

    public function testJobWontGetDispatchedInsideATransaction()
    {
        $this->app->singleton('db.transactions', function () {
            $transactionManager = m::mock(DatabaseTransactionsManager::class);
            $transactionManager->shouldReceive('addCallback')->once()->andReturn(null);

            return $transactionManager;
        });

        Bus::dispatch(new QueueConnectionTestJob);
    }

    public function testJobWillGetDispatchedInsideATransactionWhenExplicitlyIndicated()
    {
        $this->app->singleton('db.transactions', function () {
            $transactionManager = m::mock(DatabaseTransactionsManager::class);
            $transactionManager->shouldNotReceive('addCallback')->andReturn(null);

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

            return $transactionManager;
        });

        try {
            Bus::dispatch((new QueueConnectionTestJob)->afterCommit());
        } catch (SqsException) {
            // This job was dispatched
        }
    }

    /**
     * @dataProvider connectionQueueDataProvider
     */
    public function testStuff($job, $connection, $queue, $setUp = null)
    {
        ($setUp ?? fn () => null)();

        Bus::dispatch($job);

        $jobs = DB::connection()->table('jobs')->get();
        $this->assertCount(1, $jobs);
        $payload = json_decode($jobs[0]->payload);
        $this->assertSame($connection, $payload->connection);
        $this->assertSame($queue, $payload->queue);
    }

    public static function connectionQueueDataProvider()
    {
        return [
            'null null' => [new ConnectionAndQueueJob(connection: null, queue: null), null, 'default'],
            'database null' => [new ConnectionAndQueueJob(connection: 'database', queue: null), 'database', 'default'],
            'database named-queue' => [new ConnectionAndQueueJob(connection: 'database', queue: 'named-queue'), 'database', 'named-queue'],
            'database configured-default' => [new ConnectionAndQueueJob(connection: 'database', queue: null), 'database', 'configured-default', function () {
                Config::set('queue.connections.database.queue', 'configured-default');
            }],
        ];
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

class ConnectionAndQueueJob implements ShouldQueue
{
    public function __construct(public $connection = null, public $queue = null)
    {
        //
    }

    public function handle()
    {
        //
    }
}
