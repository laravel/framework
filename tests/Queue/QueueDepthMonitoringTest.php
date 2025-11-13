<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\QueueDepthExceeded;
use Illuminate\Queue\QueueManager;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class QueueDepthMonitoringTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testCanSetDepthThreshold()
    {
        $app = $this->getApp();
        $manager = new QueueManager($app);

        $result = $manager->setDepthThreshold('emails', 100);

        $this->assertSame($manager, $result);
        $this->assertEquals(100, $manager->getDepthThreshold('emails'));
    }

    public function testCanGetDepthThreshold()
    {
        $app = $this->getApp();
        $manager = new QueueManager($app);

        $manager->setDepthThreshold('emails', 100);
        $threshold = $manager->getDepthThreshold('emails');

        $this->assertEquals(100, $threshold);
    }

    public function testGetDepthThresholdReturnsNullWhenNotSet()
    {
        $app = $this->getApp();
        $manager = new QueueManager($app);

        $this->assertNull($manager->getDepthThreshold('emails'));
    }

    public function testCheckDepthAndNotifyDispatchesEventWhenThresholdExceeded()
    {
        $app = $this->getApp();
        $manager = new QueueManager($app);

        // Setup connection mock
        $queue = m::mock(stdClass::class);
        $queue->shouldReceive('setConnectionName')->andReturnSelf();
        $queue->shouldReceive('setContainer')->andReturnSelf();
        $queue->shouldReceive('size')->with('emails')->andReturn(150);

        $connector = m::mock(stdClass::class);
        $connector->shouldReceive('connect')->andReturn($queue);

        $manager->addConnector('sync', function () use ($connector) {
            return $connector;
        });

        // Set threshold
        $manager->setDepthThreshold('emails', 100);

        // Mock events dispatcher
        $eventDispatched = false;
        $dispatchedEvent = null;

        $app['events'] = m::mock(Dispatcher::class);
        $app['events']->shouldReceive('dispatch')->once()->andReturnUsing(function ($event) use (&$eventDispatched, &$dispatchedEvent) {
            $eventDispatched = true;
            $dispatchedEvent = $event;
        });

        // Check and notify
        $result = $manager->checkDepthAndNotify('sync', 'emails');

        $this->assertTrue($result);
        $this->assertTrue($eventDispatched);
        $this->assertInstanceOf(QueueDepthExceeded::class, $dispatchedEvent);
        $this->assertEquals('sync', $dispatchedEvent->connection);
        $this->assertEquals('emails', $dispatchedEvent->queue);
        $this->assertEquals(150, $dispatchedEvent->size);
        $this->assertEquals(100, $dispatchedEvent->threshold);
    }

    public function testCheckDepthAndNotifyDoesNotDispatchEventWhenBelowThreshold()
    {
        $app = $this->getApp();
        $manager = new QueueManager($app);

        // Setup connection mock
        $queue = m::mock(stdClass::class);
        $queue->shouldReceive('setConnectionName')->andReturnSelf();
        $queue->shouldReceive('setContainer')->andReturnSelf();
        $queue->shouldReceive('size')->with('emails')->andReturn(50);

        $connector = m::mock(stdClass::class);
        $connector->shouldReceive('connect')->andReturn($queue);

        $manager->addConnector('sync', function () use ($connector) {
            return $connector;
        });

        // Set threshold
        $manager->setDepthThreshold('emails', 100);

        // Mock events dispatcher - should not be called
        $app['events'] = m::mock(Dispatcher::class);
        $app['events']->shouldReceive('dispatch')->never();

        // Check and notify
        $result = $manager->checkDepthAndNotify('sync', 'emails');

        $this->assertFalse($result);
    }

    public function testCheckDepthAndNotifyReturnsFalseWhenNoThresholdSet()
    {
        $app = $this->getApp();
        $manager = new QueueManager($app);

        // No threshold set - should return false
        $result = $manager->checkDepthAndNotify('sync', 'emails');

        $this->assertFalse($result);
    }

    protected function getApp()
    {
        $app = new Container;

        $app['config'] = [
            'queue.default' => 'sync',
            'queue.connections.sync' => ['driver' => 'sync'],
        ];

        $app['cache'] = new class
        {
            private $store;

            public function store($name = null)
            {
                if (! $this->store) {
                    $this->store = new Repository(new ArrayStore);
                }

                return $this->store;
            }
        };

        $app['events'] = m::mock(Dispatcher::class);

        return $app;
    }
}
