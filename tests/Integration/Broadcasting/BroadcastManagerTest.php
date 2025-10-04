<?php

namespace Illuminate\Tests\Integration\Broadcasting;

use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Broadcasting\UniqueBroadcastEvent;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;

class BroadcastManagerTest extends TestCase
{
    public function testEventCanBeBroadcastNow()
    {
        Bus::fake();
        Queue::fake();

        Broadcast::queue(new TestEventNow);

        Bus::assertDispatched(BroadcastEvent::class);
        Queue::assertNotPushed(BroadcastEvent::class);
    }

    public function testEventsCanBeBroadcast()
    {
        Bus::fake();
        Queue::fake();

        Broadcast::queue(new TestEvent);

        Bus::assertNotDispatched(BroadcastEvent::class);
        Queue::assertPushed(BroadcastEvent::class);
    }

    public function testEventsCanBeRescued()
    {
        Bus::fake();
        Queue::fake();

        Broadcast::queue(new TestEventRescue);

        Bus::assertNotDispatched(BroadcastEvent::class);
        Queue::assertPushed(BroadcastEvent::class);
    }

    public function testNowEventsCanBeRescued()
    {
        Bus::fake();
        Queue::fake();

        Broadcast::queue(new TestEventNowRescue);

        Bus::assertDispatched(BroadcastEvent::class);
        Queue::assertNotPushed(BroadcastEvent::class);
    }

    public function testUniqueEventsCanBeBroadcast()
    {
        Bus::fake();
        Queue::fake();

        Broadcast::queue(new TestEventUnique);

        Bus::assertNotDispatched(UniqueBroadcastEvent::class);
        Queue::assertPushed(UniqueBroadcastEvent::class);

        $lockKey = 'laravel_unique_job:'.UniqueBroadcastEvent::class.':'.TestEventUnique::class;
        $this->assertFalse($this->app->get(Cache::class)->lock($lockKey, 10)->get());
    }

    public function testThrowExceptionWhenUnknownStoreIsUsed()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Broadcast connection [alien_connection] is not defined.');

        $userConfig = [
            'broadcasting' => [
                'connections' => [
                    'my_connection' => [
                        'driver' => 'pusher',
                    ],
                ],
            ],
        ];

        $app = $this->getApp($userConfig);

        $broadcastManager = new BroadcastManager($app);

        $broadcastManager->connection('alien_connection');
    }

    public function testThrowExceptionWhenUnsupportedDriverIsUsed()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver [unsupported_driver] is not supported.');

        $userConfig = [
            'broadcasting' => [
                'connections' => [
                    'my_connection' => [
                        'driver' => 'unsupported_driver',
                    ],
                ],
            ],
        ];

        $app = $this->getApp($userConfig);

        $broadcastManager = new BroadcastManager($app);

        $broadcastManager->connection('my_connection');
    }

    public function testThrowExceptionWithConnectionNameWhenBuiltInDriverCreationFails()
    {
        $userConfig = [
            'broadcasting' => [
                'connections' => [
                    'redis_connection' => [
                        'driver' => 'redis',
                        'connection' => 'invalid_connection',
                    ],
                ],
            ],
        ];

        $app = $this->getApp($userConfig);
        $app->singleton('redis', function () {
            throw new \RuntimeException('Redis service not available');
        });

        $broadcastManager = new BroadcastManager($app);

        try {
            $broadcastManager->connection('redis_connection');
            $this->fail('Expected BroadcastException was not thrown');
        } catch (BroadcastException $e) {
            $this->assertStringContainsString('Failed to create broadcaster for connection "redis_connection"', $e->getMessage());
            $this->assertStringContainsString('Redis service not available', $e->getMessage());
            $this->assertInstanceOf(\RuntimeException::class, $e->getPrevious());
        }
    }

    public function testThrowExceptionWhenPusherDriverCreationFailsWithMissingConfig()
    {
        $userConfig = [
            'broadcasting' => [
                'connections' => [
                    'pusher_connection' => [
                        'driver' => 'pusher',
                        // Missing required 'key', 'secret', 'app_id' configuration
                    ],
                ],
            ],
        ];

        $app = $this->getApp($userConfig);

        $broadcastManager = new BroadcastManager($app);

        try {
            $broadcastManager->connection('pusher_connection');
            $this->fail('Expected BroadcastException was not thrown');
        } catch (BroadcastException $e) {
            $this->assertStringContainsString('Failed to create broadcaster for connection "pusher_connection"', $e->getMessage());
            $this->assertNotNull($e->getPrevious());
        }
    }

    public function testThrowExceptionWhenPusherDriverCreationFailsWithNullConfig()
    {
        $userConfig = [
            'broadcasting' => [
                'connections' => [
                    'pusher_connection' => [
                        'driver' => 'pusher',
                        'key' => null,
                        'secret' => null,
                        'app_id' => null,
                    ],
                ],
            ],
        ];

        $app = $this->getApp($userConfig);

        $broadcastManager = new BroadcastManager($app);

        try {
            $broadcastManager->connection('pusher_connection');
            $this->fail('Expected BroadcastException was not thrown');
        } catch (BroadcastException $e) {
            $this->assertStringContainsString('Failed to create broadcaster for connection "pusher_connection"', $e->getMessage());
            $this->assertNotNull($e->getPrevious());
        }
    }

    protected function getApp(array $userConfig)
    {
        $app = new Container;
        $app->singleton('config', fn () => new Repository($userConfig));

        return $app;
    }
}

class TestEvent implements ShouldBroadcast
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\Channel[]
     */
    public function broadcastOn()
    {
        //
    }
}

class TestEventNow implements ShouldBroadcastNow
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\Channel[]
     */
    public function broadcastOn()
    {
        //
    }
}

class TestEventUnique implements ShouldBroadcast, ShouldBeUnique
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\Channel[]
     */
    public function broadcastOn()
    {
        //
    }
}

class TestEventRescue implements ShouldBroadcast, ShouldRescue
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\Channel[]
     */
    public function broadcastOn()
    {
        //
    }
}

class TestEventNowRescue implements ShouldBroadcastNow, ShouldRescue
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\Channel[]
     */
    public function broadcastOn()
    {
        //
    }
}
