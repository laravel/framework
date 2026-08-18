<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\AfterCommit;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;

#[WithConfig('queue.default', 'sync')]
class AfterCommitAttributeTest extends TestCase
{
    /**
     * Whether the dispatched job has been handled.
     *
     * @var bool
     */
    public static $handled = false;

    /**
     * The database transactions manager instance.
     *
     * @var \Illuminate\Database\DatabaseTransactionsManager
     */
    protected $transactions;

    protected function setUp(): void
    {
        parent::setUp();

        static::$handled = false;

        $this->app->instance('db.transactions', $this->transactions = new DatabaseTransactionsManager);
    }

    public function testJobWithoutTheAttributeIsDispatchedInsideATransaction()
    {
        $this->transactions->begin('testing', 1);

        Bus::dispatch(new AfterCommitAttributeTestJob);

        $this->assertTrue(static::$handled);
    }

    public function testJobWithTheAttributeIsNotDispatchedUntilTheTransactionCommits()
    {
        $this->transactions->begin('testing', 1);

        Bus::dispatch(new AfterCommitAttributeTestJobWithAttribute);

        $this->assertFalse(static::$handled);

        $this->transactions->commit('testing', 1, 0);

        $this->assertTrue(static::$handled);
    }

    public function testJobWithTheAttributeIsNeverDispatchedWhenTheTransactionRollsBack()
    {
        $this->transactions->begin('testing', 1);

        Bus::dispatch(new AfterCommitAttributeTestJobWithAttribute);

        $this->transactions->rollback('testing', 0);

        $this->assertFalse(static::$handled);
    }

    public function testQueuedListenerWithTheAttributeIsNotDispatchedUntilTheTransactionCommits()
    {
        Event::listen(AfterCommitAttributeTestEvent::class, AfterCommitAttributeTestListener::class);

        $this->transactions->begin('testing', 1);

        Event::dispatch(new AfterCommitAttributeTestEvent);

        $this->assertFalse(static::$handled);

        $this->transactions->commit('testing', 1, 0);

        $this->assertTrue(static::$handled);
    }
}

class AfterCommitAttributeTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        AfterCommitAttributeTest::$handled = true;
    }
}

#[AfterCommit]
class AfterCommitAttributeTestJobWithAttribute extends AfterCommitAttributeTestJob
{
    //
}

class AfterCommitAttributeTestEvent
{
    //
}

#[AfterCommit]
class AfterCommitAttributeTestListener implements ShouldQueue
{
    public function handle($event)
    {
        AfterCommitAttributeTest::$handled = true;
    }
}
