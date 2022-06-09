<?php

namespace Illuminate\Tests\Database;

use Carbon\CarbonInterval;
use Illuminate\Database\Connection;
use Illuminate\Events\Dispatcher;
use PDO;
use PHPUnit\Framework\TestCase;

class QueryDurationThresholdTest extends TestCase
{
    public function testItCanHandleReachingADurationThresholdInTheDb()
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $connection->setEventDispatcher(new Dispatcher());
        $called = 0;
        $connection->handleExceedingQueryDuration(CarbonInterval::milliseconds(1.1), function () use (&$called) {
            $called++;
        });

        $connection->logQuery('xxxx', [], 1.0);
        $connection->logQuery('xxxx', [], 0.1);
        $this->assertSame(0, $called);

        $connection->logQuery('xxxx', [], 0.1);
        $this->assertSame(1, $called);
    }

    public function testItIsOnlyCalledOnce()
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $connection->setEventDispatcher(new Dispatcher());
        $called = 0;
        $connection->handleExceedingQueryDuration(CarbonInterval::milliseconds(1), function () use (&$called) {
            $called++;
        });

        $connection->logQuery('xxxx', [], 1);
        $connection->logQuery('xxxx', [], 1);
        $connection->logQuery('xxxx', [], 1);

        $this->assertSame(1, $called);
    }

    public function testItCanSpecifyMultipleHandlersWithTheSameIntervals()
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $connection->setEventDispatcher(new Dispatcher());
        $called = [];
        $connection->handleExceedingQueryDuration(CarbonInterval::milliseconds(1), function () use (&$called) {
            $called['a'] = true;
        });
        $connection->handleExceedingQueryDuration(CarbonInterval::milliseconds(1), function () use (&$called) {
            $called['b'] = true;
        });

        $connection->logQuery('xxxx', [], 1);
        $connection->logQuery('xxxx', [], 1);

        $this->assertSame([
            'a' => true,
            'b' => true,
        ], $called);
    }

    public function testItCanSpecifyMultipleHandlersWithDifferentIntervals()
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $connection->setEventDispatcher(new Dispatcher());
        $called = [];
        $connection->handleExceedingQueryDuration(CarbonInterval::milliseconds(1), function () use (&$called) {
            $called['a'] = true;
        });
        $connection->handleExceedingQueryDuration(CarbonInterval::milliseconds(2), function () use (&$called) {
            $called['b'] = true;
        });

        $connection->logQuery('xxxx', [], 1);
        $connection->logQuery('xxxx', [], 1);
        $this->assertSame([
            'a' => true,
        ], $called);

        $connection->logQuery('xxxx', [], 1);
        $this->assertSame([
            'a' => true,
            'b' => true,
        ], $called);
    }

    public function testItHasAccessToConnectionInHandler()
    {
        $connection = new Connection(new PDO('sqlite::memory:'), '', '', ['name' => 'expected-name']);
        $connection->setEventDispatcher(new Dispatcher());
        $name = null;
        $connection->handleExceedingQueryDuration(CarbonInterval::milliseconds(1), function ($connection) use (&$name) {
            $name = $connection->getName();
        });

        $connection->logQuery('xxxx', [], 1);
        $connection->logQuery('xxxx', [], 1);

        $this->assertSame('expected-name', $name);
    }

    public function testItHasSpecifyThresholdWithFloat()
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $connection->setEventDispatcher(new Dispatcher());
        $called = false;
        $connection->handleExceedingQueryDuration(1.1, function () use (&$called) {
            $called = true;
        });

        $connection->logQuery('xxxx', [], 1.1);
        $this->assertFalse($called);

        $connection->logQuery('xxxx', [], 0.1);
        $this->assertTrue($called);
    }

    public function testItHasSpecifyThresholdWithInt()
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $connection->setEventDispatcher(new Dispatcher());
        $called = false;
        $connection->handleExceedingQueryDuration(2, function () use (&$called) {
            $called = true;
        });

        $connection->logQuery('xxxx', [], 1.1);
        $this->assertFalse($called);

        $connection->logQuery('xxxx', [], 1.0);
        $this->assertTrue($called);
    }

    public function testItCanResetTotalQueryDuration()
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $connection->setEventDispatcher(new Dispatcher());

        $connection->logQuery('xxxx', [], 1.1);
        $this->assertSame(1.1, $connection->totalQueryDuration());
        $connection->logQuery('xxxx', [], 1.1);
        $this->assertSame(2.2, $connection->totalQueryDuration());

        $connection->resetTotalQueryDuration();
        $this->assertSame(0.0, $connection->totalQueryDuration());
    }

    public function testItCanRestoreAlreadyRunHandlers()
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $connection->setEventDispatcher(new Dispatcher());
        $called = 0;
        $connection->handleExceedingQueryDuration(CarbonInterval::milliseconds(1), function () use (&$called) {
            $called++;
        });

        $connection->logQuery('xxxx', [], 1);
        $connection->logQuery('xxxx', [], 1);
        $connection->logQuery('xxxx', [], 1);
        $this->assertSame(1, $called);

        $connection->restoreAlreadyRunQueryDurationHandlers();
        $connection->logQuery('xxxx', [], 1);
        $connection->logQuery('xxxx', [], 1);
        $connection->logQuery('xxxx', [], 1);
        $this->assertSame(2, $called);

        $connection->restoreAlreadyRunQueryDurationHandlers();
        $connection->logQuery('xxxx', [], 1);
        $connection->logQuery('xxxx', [], 1);
        $connection->logQuery('xxxx', [], 1);
        $this->assertSame(3, $called);
    }
}
