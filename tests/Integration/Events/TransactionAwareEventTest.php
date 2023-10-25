<?php

namespace Illuminate\Tests\Integration\Events;

use Illuminate\Contracts\Events\TransactionAware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class TransactionAwareEventTest extends TestCase
{
    protected function tearDown(): void
    {
        TransactionUnawareTestEvent::$ran = false;
        TransactionAwareTestEvent::$ran = false;

        m::close();
    }

    public function testEventIsDispatchedIfThereIsNoTransaction()
    {
        Event::listen(TransactionAwareTestEvent::class, TransactionAwareListener::class);

        Event::dispatch(new TransactionAwareTestEvent);

        $this->assertTrue(TransactionAwareTestEvent::$ran);
    }

    public function testEventIsNotDispatchedIfTransactionFails()
    {
        Event::listen(TransactionAwareTestEvent::class, TransactionAwareListener::class);

        try {
            DB::transaction(function () {
                Event::dispatch(new TransactionAwareTestEvent);

                throw new \Exception;
            });
        } catch (\Exception) {
        }

        $this->assertFalse(TransactionAwareTestEvent::$ran);
    }

    public function testEventIsDispatchedIfTransactionSucceeds()
    {
        Event::listen(TransactionAwareTestEvent::class, TransactionAwareListener::class);

        DB::transaction(function () {
            Event::dispatch(new TransactionAwareTestEvent);
        });

        $this->assertTrue(TransactionAwareTestEvent::$ran);
    }

    public function testItHandlesNestedTransactions()
    {
        // We are going to dispatch 2 different events in 2 different transactions.
        // The parent transaction will succeed, but the nested transaction is going to fail and be rolled back.
        // We want to ensure the event dispatched on the child transaction does not get published, since it failed,
        // however, the event dispatched on the parent transaction should still be dispatched as usual.
        Event::listen(TransactionAwareTestEvent::class, TransactionAwareListener::class);
        Event::listen(AnotherTransactionAwareTestEvent::class, AnotherTransactionAwareListener::class);

        DB::transaction(function () {
            try {
                DB::transaction(function () {
                    // This event should not be dispatched since the transaction is going to fail.
                    Event::dispatch(new TransactionAwareTestEvent);
                    throw new \Exception;
                });
            } catch (\Exception) {

            }

            // This event should be dispatched, as the parent transaction does not fail.
            Event::dispatch(new AnotherTransactionAwareTestEvent);
        });

        $this->assertFalse(TransactionAwareTestEvent::$ran);
        $this->assertTrue(AnotherTransactionAwareTestEvent::$ran);
    }
}

class TransactionUnawareTestEvent
{
    public static $ran = false;
}

class TransactionAwareTestEvent implements TransactionAware
{
    public static $ran = false;
}

class AnotherTransactionAwareTestEvent implements TransactionAware
{
    public static $ran = false;
}

class TransactionAwareListener
{
    public function handle(object $event)
    {
        $event::$ran = true;
    }
}

class AnotherTransactionAwareListener
{
    public function handle(object $event)
    {
        $event::$ran = true;
    }
}
