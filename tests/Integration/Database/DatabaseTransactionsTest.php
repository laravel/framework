<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\DB;

class DatabaseTransactionsTest extends DatabaseTestCase
{
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
            } catch (\Exception) {}
        });

        $this->assertTrue($firstObject->ran);
        $this->assertTrue($secondObject->ran);
        $this->assertFalse($thirdObject->ran);
    }
}

class TestObjectForTransactions
{
    public bool $ran = false;

    public function handle()
    {
        $this->ran = true;
    }
}
