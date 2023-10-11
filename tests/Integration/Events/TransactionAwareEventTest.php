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
}

class TransactionUnawareTestEvent
{
    public static $ran = false;
}

class TransactionAwareTestEvent implements TransactionAware
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
