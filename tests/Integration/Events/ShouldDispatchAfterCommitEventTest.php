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
        AnotherShouldDispatchAfterCommitTestEvent::$ran = false;

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

    public function testItOnlyDispatchesNestedTransactionsEventsAfterTheRootTransactionIsCommitted()
    {
        Event::listen(ShouldDispatchAfterCommitTestEvent::class, ShouldDispatchAfterCommitListener::class);
        Event::listen(AnotherShouldDispatchAfterCommitTestEvent::class, AnotherShouldDispatchAfterCommitListener::class);

        DB::transaction(function () {
            Event::dispatch(new AnotherShouldDispatchAfterCommitTestEvent);

            DB::transaction(function () {
                Event::dispatch(new ShouldDispatchAfterCommitTestEvent);
            });

            // Although the child transaction has been concluded, the parent transaction has not.
            // The event dispatched on the child transaction should not have been dispatched.
            $this->assertFalse(ShouldDispatchAfterCommitTestEvent::$ran);
            $this->assertFalse(AnotherShouldDispatchAfterCommitTestEvent::$ran);
        });

        // Now that the parent transaction has been committed, the event
        // on the child transaction should also have been dispatched.
        $this->assertTrue(ShouldDispatchAfterCommitTestEvent::$ran);
        $this->assertTrue(AnotherShouldDispatchAfterCommitTestEvent::$ran);
    }

    public function testItOnlyDispatchesNestedTransactionsEventsAfterTheRootTransactionIsCommitedDifferentOrder()
    {
        Event::listen(ShouldDispatchAfterCommitTestEvent::class, ShouldDispatchAfterCommitListener::class);
        Event::listen(AnotherShouldDispatchAfterCommitTestEvent::class, AnotherShouldDispatchAfterCommitListener::class);

        DB::transaction(function () {
            DB::transaction(function () {
                Event::dispatch(new ShouldDispatchAfterCommitTestEvent);
            });

            // Although the child transaction has been concluded, the parent transaction has not.
            // The event dispatched on the child transaction should not have been dispatched.
            $this->assertFalse(ShouldDispatchAfterCommitTestEvent::$ran);

            // The main difference with this test is that we dispatch an event on the parent transaction
            // at the end. This is important due to how the DatabaseTransactionsManager works.
            Event::dispatch(new AnotherShouldDispatchAfterCommitTestEvent);
        });

        // Now that the parent transaction has been committed, the event
        // on the child transaction should also have been dispatched.
        $this->assertTrue(ShouldDispatchAfterCommitTestEvent::$ran);
        $this->assertTrue(AnotherShouldDispatchAfterCommitTestEvent::$ran);
    }

    public function testItDoesNotDispatchAfterCommitEventsImmediatelyIfASiblingTransactionIsCommittedFirst()
    {
        Event::listen(ShouldDispatchAfterCommitTestEvent::class, ShouldDispatchAfterCommitListener::class);

        DB::transaction(function () {
            DB::transaction(function () {
            });

            Event::dispatch(new ShouldDispatchAfterCommitTestEvent);

            $this->assertFalse(ShouldDispatchAfterCommitTestEvent::$ran);
        });

        $this->assertTrue(ShouldDispatchAfterCommitTestEvent::$ran);
    }

    public function testChildEventsAreNotDispatchedIfParentTransactionFails()
    {
        Event::listen(ShouldDispatchAfterCommitTestEvent::class, ShouldDispatchAfterCommitListener::class);

        try {
            DB::transaction(function () {
                DB::transaction(function () {
                    Event::dispatch(new ShouldDispatchAfterCommitTestEvent);
                });

                $this->assertFalse(ShouldDispatchAfterCommitTestEvent::$ran);

                throw new \Exception;
            });
        } catch (\Exception $e) {
            //
        }

        DB::transaction(fn () => true);

        // Should not have ran because parent transaction failed...
        $this->assertFalse(ShouldDispatchAfterCommitTestEvent::$ran);
    }

    public function testItHandlesNestedTransactionsWhereTheSecondOneFails()
    {
        Event::listen(ShouldDispatchAfterCommitTestEvent::class, ShouldDispatchAfterCommitListener::class);
        Event::listen(AnotherShouldDispatchAfterCommitTestEvent::class, AnotherShouldDispatchAfterCommitListener::class);

        DB::transaction(function () {
            DB::transaction(function () {
                Event::dispatch(new ShouldDispatchAfterCommitTestEvent());
            });

            try {
                DB::transaction(function () {
                    // This event should not be dispatched since the transaction is going to fail.
                    Event::dispatch(new AnotherShouldDispatchAfterCommitTestEvent);
                    throw new \Exception;
                });
            } catch (\Exception $e) {
            }
        });

        $this->assertTrue(ShouldDispatchAfterCommitTestEvent::$ran);
        $this->assertFalse(AnotherShouldDispatchAfterCommitTestEvent::$ran);
    }

    public function testChildCallbacksShouldNotBeDispatchedIfTheirParentFails()
    {
        Event::listen(ShouldDispatchAfterCommitTestEvent::class, ShouldDispatchAfterCommitListener::class);

        DB::transaction(function () {
            try {
                DB::transaction(function () {
                    DB::transaction(function () {
                        Event::dispatch(new ShouldDispatchAfterCommitTestEvent());
                    });

                    throw new \Exception;
                });
            } catch (\Exception $e) {
                //
            }
        });

        $this->assertFalse(ShouldDispatchAfterCommitTestEvent::$ran);
    }

    public function testItHandlesFailuresWithTransactionsTwoLevelsHigher()
    {
        Event::listen(ShouldDispatchAfterCommitTestEvent::class, ShouldDispatchAfterCommitListener::class);
        Event::listen(AnotherShouldDispatchAfterCommitTestEvent::class, AnotherShouldDispatchAfterCommitListener::class);

        DB::transaction(function () { // lv 1
            DB::transaction(function () { // lv 2
                DB::transaction(fn () => Event::dispatch(new ShouldDispatchAfterCommitTestEvent()));
                // lv 2
            });

            try {
                DB::transaction(function () { // lv 2
                    // This event should not be dispatched since the transaction is going to fail.
                    Event::dispatch(new AnotherShouldDispatchAfterCommitTestEvent);
                    throw new \Exception;
                });
            } catch (\Exception $e) {
            }
        });

        $this->assertTrue(ShouldDispatchAfterCommitTestEvent::$ran);
        $this->assertFalse(AnotherShouldDispatchAfterCommitTestEvent::$ran);
    }

    public function testCommittedTransactionThatWasDeeplyNestedIsRemovedIfTopLevelFails()
    {
        Event::listen(ShouldDispatchAfterCommitTestEvent::class, ShouldDispatchAfterCommitListener::class);

        DB::transaction(function () {
            try {
                DB::transaction(function () {
                    DB::transaction(function () {
                        DB::transaction(function () {
                            DB::transaction(fn () => Event::dispatch(new ShouldDispatchAfterCommitTestEvent()));
                        });
                    });

                    throw new \Exception;
                });
            } catch (\Exception $e) {
            }
        });

        $this->assertFalse(ShouldDispatchAfterCommitTestEvent::$ran);
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
