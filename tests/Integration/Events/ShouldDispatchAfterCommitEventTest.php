<?php

namespace Illuminate\Tests\Integration\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class ShouldDispatchAfterCommitEventTest extends TestCase
{
    protected function tearDown(): void
    {
        TransactionUnawareTestEvent::$ran = false;
        ShouldDispatchAfterCommitTestEvent::$ran = false;

        m::close();
    }

    public function testEventIsDispatchedIfThereIsNoTransaction()
    {
        Event::listen(ShouldDispatchAfterCommitTestEvent::class, ShouldDispatchAfterCommitListener::class);

        Event::dispatch(new ShouldDispatchAfterCommitTestEvent);

        $this->assertTrue(ShouldDispatchAfterCommitTestEvent::$ran);
    }

    public function testEventIsNotDispatchedIfTransactionFails()
    {
        Event::listen(ShouldDispatchAfterCommitTestEvent::class, ShouldDispatchAfterCommitListener::class);

        try {
            DB::transaction(function () {
                Event::dispatch(new ShouldDispatchAfterCommitTestEvent);

                throw new \Exception;
            });
        } catch (\Exception) {
        }

        $this->assertFalse(ShouldDispatchAfterCommitTestEvent::$ran);
    }

    public function testEventIsDispatchedIfTransactionSucceeds()
    {
        Event::listen(ShouldDispatchAfterCommitTestEvent::class, ShouldDispatchAfterCommitListener::class);

        DB::transaction(function () {
            Event::dispatch(new ShouldDispatchAfterCommitTestEvent);
        });

        $this->assertTrue(ShouldDispatchAfterCommitTestEvent::$ran);
    }

    public function testItHandlesNestedTransactions()
    {
        // We are going to dispatch 2 different events in 2 different transactions.
        // The parent transaction will succeed, but the nested transaction is going to fail and be rolled back.
        // We want to ensure the event dispatched on the child transaction does not get published, since it failed,
        // however, the event dispatched on the parent transaction should still be dispatched as usual.
        Event::listen(ShouldDispatchAfterCommitTestEvent::class, ShouldDispatchAfterCommitListener::class);
        Event::listen(AnotherShouldDispatchAfterCommitTestEvent::class, AnotherShouldDispatchAfterCommitListener::class);

        DB::transaction(function () {
            try {
                DB::transaction(function () {
                    // This event should not be dispatched since the transaction is going to fail.
                    Event::dispatch(new ShouldDispatchAfterCommitTestEvent);
                    throw new \Exception;
                });
            } catch (\Exception) {
            }

            // This event should be dispatched, as the parent transaction does not fail.
            Event::dispatch(new AnotherShouldDispatchAfterCommitTestEvent);
        });

        $this->assertFalse(ShouldDispatchAfterCommitTestEvent::$ran);
        $this->assertTrue(AnotherShouldDispatchAfterCommitTestEvent::$ran);
    }
}

class TransactionUnawareTestEvent
{
    public static $ran = false;
}

class ShouldDispatchAfterCommitTestEvent implements ShouldDispatchAfterCommit
{
    public static $ran = false;
}

class AnotherShouldDispatchAfterCommitTestEvent implements ShouldDispatchAfterCommit
{
    public static $ran = false;
}

class ShouldDispatchAfterCommitListener
{
    public function handle(object $event)
    {
        $event::$ran = true;
    }
}

class AnotherShouldDispatchAfterCommitListener
{
    public function handle(object $event)
    {
        $event::$ran = true;
    }
}
