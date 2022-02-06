<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\DatabaseTransactionsManager;
use PHPUnit\Framework\TestCase;

class DatabaseTransactionsManagerTest extends TestCase
{
    public function testBeginningTransactions()
    {
        $manager = (new DatabaseTransactionsManager);

        $manager->begin('default', 1);
        $manager->begin('default', 2);
        $manager->begin('admin', 1);

        $this->assertCount(3, $manager->getTransactions());
        $this->assertSame('default', $manager->getTransactions()[0]->connection);
        $this->assertEquals(1, $manager->getTransactions()[0]->level);
        $this->assertSame('default', $manager->getTransactions()[1]->connection);
        $this->assertEquals(2, $manager->getTransactions()[1]->level);
        $this->assertSame('admin', $manager->getTransactions()[2]->connection);
        $this->assertEquals(1, $manager->getTransactions()[2]->level);
    }

    public function testRollingBackTransactions()
    {
        $manager = (new DatabaseTransactionsManager);

        $manager->begin('default', 1);
        $manager->begin('default', 2);
        $manager->begin('admin', 1);

        $manager->rollback('default', 1);

        $this->assertCount(2, $manager->getTransactions());

        $this->assertSame('default', $manager->getTransactions()[0]->connection);
        $this->assertEquals(1, $manager->getTransactions()[0]->level);

        $this->assertSame('admin', $manager->getTransactions()[1]->connection);
        $this->assertEquals(1, $manager->getTransactions()[1]->level);
    }

    public function testRollingBackTransactionsAllTheWay()
    {
        $manager = (new DatabaseTransactionsManager);

        $manager->begin('default', 1);
        $manager->begin('default', 2);
        $manager->begin('admin', 1);

        $manager->rollback('default', 0);

        $this->assertCount(1, $manager->getTransactions());

        $this->assertSame('admin', $manager->getTransactions()[0]->connection);
        $this->assertEquals(1, $manager->getTransactions()[0]->level);
    }

    public function testCommittingTransactions()
    {
        $manager = (new DatabaseTransactionsManager);

        $manager->begin('default', 1);
        $manager->begin('default', 2);
        $manager->begin('admin', 1);

        $manager->commit('default');

        $this->assertCount(1, $manager->getTransactions());

        $this->assertSame('admin', $manager->getTransactions()[0]->connection);
        $this->assertEquals(1, $manager->getTransactions()[0]->level);
    }

    public function testCallbacksAreAddedToTheCurrentTransaction()
    {
        $callbacks = [];

        $manager = (new DatabaseTransactionsManager);

        $manager->begin('default', 1);

        $manager->addCallback(function () use (&$callbacks) {
        });

        $manager->begin('default', 2);

        $manager->begin('admin', 1);

        $manager->addCallback(function () use (&$callbacks) {
        });

        $this->assertCount(1, $manager->getTransactions()[0]->getCallbacks());
        $this->assertCount(0, $manager->getTransactions()[1]->getCallbacks());
        $this->assertCount(1, $manager->getTransactions()[2]->getCallbacks());
    }

    public function testCommittingTransactionsExecutesCallbacks()
    {
        $callbacks = [];

        $manager = (new DatabaseTransactionsManager);

        $manager->begin('default', 1);

        $manager->addCallback(function () use (&$callbacks) {
            $callbacks[] = ['default', 1];
        });

        $manager->begin('default', 2);

        $manager->addCallback(function () use (&$callbacks) {
            $callbacks[] = ['default', 2];
        });

        $manager->begin('admin', 1);

        $manager->commit('default');

        $this->assertCount(2, $callbacks);
        $this->assertEquals(['default', 1], $callbacks[0]);
        $this->assertEquals(['default', 2], $callbacks[1]);
    }

    public function testCommittingExecutesOnlyCallbacksOfTheConnection()
    {
        $callbacks = [];

        $manager = (new DatabaseTransactionsManager);

        $manager->begin('default', 1);

        $manager->addCallback(function () use (&$callbacks) {
            $callbacks[] = ['default', 1];
        });

        $manager->begin('default', 2);
        $manager->begin('admin', 1);

        $manager->addCallback(function () use (&$callbacks) {
            $callbacks[] = ['admin', 1];
        });

        $manager->commit('default');

        $this->assertCount(1, $callbacks);
        $this->assertEquals(['default', 1], $callbacks[0]);
    }

    public function testCallbackIsExecutedIfNoTransactions()
    {
        $callbacks = [];

        $manager = (new DatabaseTransactionsManager);

        $manager->addCallback(function () use (&$callbacks) {
            $callbacks[] = ['default', 1];
        });

        $this->assertCount(1, $callbacks);
        $this->assertEquals(['default', 1], $callbacks[0]);
    }
}
