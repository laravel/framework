<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\DB;

class DatabaseTransactionsTest extends DatabaseTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set([
            'database.connections.second_connection' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);
    }

    public function testTransactionCallbacks()
    {
        [$firstObject, $secondObject, $thirdObject] = [
            new TestObjectForTransactions(),
            new TestObjectForTransactions(),
            new TestObjectForTransactions(),
        ];

        DB::transaction(function () use ($secondObject, $firstObject) {
            DB::afterCommit(fn () => $firstObject->handle());

            DB::transaction(function () use ($secondObject) {
                DB::afterCommit(fn () => $secondObject->handle());
            });
        });

        $this->assertTrue($firstObject->ran);
        $this->assertTrue($secondObject->ran);
        $this->assertEquals(1, $firstObject->runs);
        $this->assertEquals(1, $secondObject->runs);
        $this->assertFalse($thirdObject->ran);
    }

    public function testTransactionCallbacksDoNotInterfereWithOneAnother()
    {
        [$firstObject, $secondObject, $thirdObject] = [
            new TestObjectForTransactions(),
            new TestObjectForTransactions(),
            new TestObjectForTransactions(),
        ];

        // The problem here is that we're initiating a base transaction, and then two nested transactions.
        // Although these two nested transactions are not the same, they share the same level (2).
        // Since they are not the same, the latter one failing should not affect the first one.
        DB::transaction(function () use ($thirdObject, $secondObject, $firstObject) { // Adds a transaction @ level 1
            DB::transaction(function () use ($firstObject) { // Adds a transaction @ level 2
                DB::afterCommit(fn () => $firstObject->handle()); // Adds a callback to be executed after transaction level 2 is committed
            });

            DB::afterCommit(fn () => $secondObject->handle()); // Adds a callback to be executed after transaction 1 @ lvl 1

            try {
                DB::transaction(function () use ($thirdObject) { // Adds a transaction 3 @ level 2
                    DB::afterCommit(fn () => $thirdObject->handle());
                    throw new \Exception(); // This should only affect callback 3, not 1, even though both share the same transaction level.
                });
            } catch (\Exception) {
            }
        });

        $this->assertTrue($firstObject->ran);
        $this->assertTrue($secondObject->ran);
        $this->assertEquals(1, $firstObject->runs);
        $this->assertEquals(1, $secondObject->runs);
        $this->assertFalse($thirdObject->ran);
    }

    public function testTransactionsDoNotAffectDifferentConnections()
    {
        [$firstObject, $secondObject, $thirdObject] = [
            new TestObjectForTransactions(),
            new TestObjectForTransactions(),
            new TestObjectForTransactions(),
        ];

        DB::transaction(function () use ($secondObject, $firstObject, $thirdObject) {
            DB::transaction(function () use ($secondObject) {
                DB::afterCommit(fn () => $secondObject->handle());
            });

            DB::afterCommit(fn () => $firstObject->handle());

            try {
                DB::connection('second_connection')->transaction(function () use ($thirdObject) {
                    DB::afterCommit(fn () => $thirdObject->handle());

                    throw new \Exception;
                });
            } catch (\Exception) {
                //
            }
        });

        $this->assertTrue($firstObject->ran);
        $this->assertTrue($secondObject->ran);
        $this->assertFalse($thirdObject->ran);
    }

    public function testAfterRollbackCallbacksAreExecuted()
    {
        $afterCommitRan = false;
        $afterRollbackRan = false;

        try {
            DB::transaction(function () use (&$afterCommitRan, &$afterRollbackRan) {
                DB::afterCommit(function () use (&$afterCommitRan) {
                    $afterCommitRan = true;
                });

                DB::afterRollBack(function () use (&$afterRollbackRan) {
                    $afterRollbackRan = true;
                });

                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException) {
            // Ignore the expected rollback exception.
        }

        $this->assertFalse($afterCommitRan);
        $this->assertTrue($afterRollbackRan);
    }

    public function testTransactionStartedInAfterRollbackCallbackDoesNotRunCallbacksOfRolledBackTransaction()
    {
        $callbacks = [];

        try {
            DB::transaction(function () use (&$callbacks) {
                DB::afterCommit(function () use (&$callbacks) {
                    $callbacks[] = 'rolled back transaction';
                });

                DB::transaction(function () use (&$callbacks) {
                    DB::afterCommit(function () use (&$callbacks) {
                        $callbacks[] = 'committed savepoint of rolled back transaction';
                    });
                });

                DB::afterRollBack(function () use (&$callbacks) {
                    DB::transaction(function () use (&$callbacks) {
                        DB::afterCommit(function () use (&$callbacks) {
                            $callbacks[] = 'transaction started in after rollback callback';
                        });
                    });

                    $callbacks[] = 'after rollback';
                });

                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException) {
            // Ignore the expected rollback exception.
        }

        $this->assertSame(['transaction started in after rollback callback', 'after rollback'], $callbacks);
    }

    public function testAfterCommitCallbackRegisteredInAfterRollbackCallbackIsExecutedImmediately()
    {
        $afterCommitRan = false;

        try {
            DB::transaction(function () use (&$afterCommitRan) {
                DB::afterRollBack(function () use (&$afterCommitRan) {
                    DB::afterCommit(function () use (&$afterCommitRan) {
                        $afterCommitRan = true;
                    });
                });

                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException) {
            // Ignore the expected rollback exception.
        }

        $this->assertTrue($afterCommitRan);
    }

    public function testRollbackCallbackThatThrowsDoesNotLeaveRolledBackTransactionBehind()
    {
        $callbacks = [];

        try {
            DB::transaction(function () use (&$callbacks) {
                DB::afterCommit(function () use (&$callbacks) {
                    $callbacks[] = 'after commit of rolled back transaction';
                });

                DB::afterRollBack(function () use (&$callbacks) {
                    $callbacks[] = 'after rollback';

                    throw new \LogicException('after rollback failed');
                });

                throw new \RuntimeException('rollback');
            });
        } catch (\LogicException) {
            // The rollback callback's exception is rethrown.
        }

        DB::transaction(function () {
            //
        });

        try {
            DB::transaction(function () {
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException|\LogicException) {
            // Ignore the expected rollback exception.
        }

        $this->assertSame(['after rollback'], $callbacks);
    }
}

class TestObjectForTransactions
{
    public $ran = false;

    public $runs = 0;

    public function handle()
    {
        $this->ran = true;
        $this->runs++;
    }
}
