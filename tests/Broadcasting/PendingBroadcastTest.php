<?php

namespace Illuminate\Tests\Broadcasting;

use Illuminate\Broadcasting\PendingBroadcast;
use Illuminate\Events\Dispatcher;
use PHPUnit\Framework\TestCase;

class PendingBroadcastTest extends TestCase
{
    public function testGetEvent()
    {
        $event = new class {
            public $data = 'test';
        };

        $pendingBroadcast = new PendingBroadcast(new Dispatcher, $event);

        $this->assertSame($event, $pendingBroadcast->getEvent());
    }

    public function testViaCallsBroadcastViaOnEvent()
    {
        $event = new class {
            public $connection = null;

            public function broadcastVia($connection)
            {
                $this->connection = $connection;
            }
        };

        $pendingBroadcast = new PendingBroadcast(new Dispatcher, $event);
        $pendingBroadcast->via('custom-connection');

        $this->assertSame('custom-connection', $event->connection);
    }

    public function testViaWithNullConnection()
    {
        $event = new class {
            public $connection = 'default';

            public function broadcastVia($connection)
            {
                $this->connection = $connection;
            }
        };

        $pendingBroadcast = new PendingBroadcast(new Dispatcher, $event);
        $pendingBroadcast->via(null);

        $this->assertNull($event->connection);
    }

    public function testViaDoesNothingWhenEventHasNoBroadcastViaMethod()
    {
        $event = new class {
            // No broadcastVia method
        };

        $pendingBroadcast = new PendingBroadcast(new Dispatcher, $event);
        $result = $pendingBroadcast->via('custom-connection');

        $this->assertSame($pendingBroadcast, $result);
    }

    public function testToOthersCallsDontBroadcastToCurrentUser()
    {
        $event = new class {
            public $excludeCurrentUser = false;

            public function dontBroadcastToCurrentUser()
            {
                $this->excludeCurrentUser = true;
            }
        };

        $pendingBroadcast = new PendingBroadcast(new Dispatcher, $event);
        $pendingBroadcast->toOthers();

        $this->assertTrue($event->excludeCurrentUser);
    }

    public function testToOthersDoesNothingWhenEventHasNoDontBroadcastToCurrentUserMethod()
    {
        $event = new class {
            // No dontBroadcastToCurrentUser method
        };

        $pendingBroadcast = new PendingBroadcast(new Dispatcher, $event);
        $result = $pendingBroadcast->toOthers();

        $this->assertSame($pendingBroadcast, $result);
    }

    public function testViaReturnsSelf()
    {
        $pendingBroadcast = new PendingBroadcast(new Dispatcher, new class {});
        $result = $pendingBroadcast->via('custom');

        $this->assertSame($pendingBroadcast, $result);
    }

    public function testToOthersReturnsSelf()
    {
        $pendingBroadcast = new PendingBroadcast(new Dispatcher, new class {});
        $result = $pendingBroadcast->toOthers();

        $this->assertSame($pendingBroadcast, $result);
    }

    public function testDestructDispatchesEvent()
    {
        $dispatchedEvents = [];

        $dispatcher = new class extends Dispatcher {
            public function dispatch($event)
            {
                $dispatchedEvents[] = $event;
                return $event;
            }
        };

        $event = new class {
            public $data = 'test';
        };

        $pendingBroadcast = new PendingBroadcast($dispatcher, $event);
        unset($pendingBroadcast);

        $this->assertCount(1, $dispatchedEvents);
        $this->assertSame($event, $dispatchedEvents[0]);
    }
}
