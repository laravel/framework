<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Foundation\Testing\DatabaseTransactionsManager;
use PHPUnit\Framework\TestCase;

class DatabaseTransactionsManagerTest extends TestCase
{
    public function testItExecutesCallbacksImmediatelyIfThereIsOnlyOneTransaction()
    {
        $testObject = new TestingDatabaseTransactionsManagerTestObject;
        $manager = new DatabaseTransactionsManager([null]);

        $manager->begin('foo', 1);

        $manager->addCallback(fn () => $testObject->handle());

        $this->assertTrue($testObject->ran);
        $this->assertEquals(1, $testObject->runs);
    }

    public function testItIgnoresTheBaseTransactionForCallbackApplicableTransactions()
    {
        $manager = new DatabaseTransactionsManager([null]);

        $manager->begin('foo', 1);
        $manager->begin('foo', 2);

        $this->assertCount(1, $manager->callbackApplicableTransactions());
        $this->assertEquals(2, $manager->callbackApplicableTransactions()[0]->level);
    }

    public function testCommittingDoesNotRemoveTheBasePendingTransaction()
    {
        $manager = new DatabaseTransactionsManager([null]);

        $manager->begin('foo', 1);

        $manager->begin('foo', 2);
        $manager->commit('foo', 2, 1);

        $this->assertCount(0, $manager->callbackApplicableTransactions());

        $manager->begin('foo', 2);

        $this->assertCount(1, $manager->callbackApplicableTransactions());
        $this->assertEquals(2, $manager->callbackApplicableTransactions()[0]->level);
    }

    public function testItExecutesCallbacksForTheSecondTransaction()
    {
        $testObject = new TestingDatabaseTransactionsManagerTestObject;
        $manager = new DatabaseTransactionsManager([null]);
        $manager->begin('foo', 1);
        $manager->begin('foo', 2);

        $manager->addCallback(fn () => $testObject->handle());

        $this->assertFalse($testObject->ran);

        $manager->commit('foo', 2, 1);
        $manager->commit('foo', 1, 0);
        $this->assertTrue($testObject->ran);
        $this->assertEquals(1, $testObject->runs);
    }

    public function testItExecutesTransactionCallbacksAtLevelOne()
    {
        $manager = new DatabaseTransactionsManager([null]);

        $this->assertFalse($manager->afterCommitCallbacksShouldBeExecuted(0));
        $this->assertTrue($manager->afterCommitCallbacksShouldBeExecuted(1));
        $this->assertFalse($manager->afterCommitCallbacksShouldBeExecuted(2));
    }

    public function testSkipsTheNumberOfConnectionsTransacting()
    {
        $manager = new DatabaseTransactionsManager([null]);

        $manager->begin('foo', 1);
        $manager->begin('foo', 2);

        $this->assertCount(1, $manager->callbackApplicableTransactions());
    }
}

class TestingDatabaseTransactionsManagerTestObject
{
    public $ran = false;

    public $runs = 0;

    public function handle()
    {
        $this->ran = true;
        $this->runs++;
    }
}
