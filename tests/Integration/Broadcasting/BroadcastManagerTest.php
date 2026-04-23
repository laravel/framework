<?php

namespace Illuminate\Tests\Integration\Broadcasting;

use Illuminate\Broadcasting\BroadcastEvent;
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
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;

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

    public function testEventsCanBeBroadcastUsingQueueRoutes()
    {
        Bus::fake();
        Queue::fake();

        Queue::route(TestEvent::class, 'broadcast-queue', 'broadcast-connection');

        Broadcast::queue(new TestEvent);
        Bus::assertNotDispatched(BroadcastEvent::class);
        Queue::connection('broadcast-connection')->assertPushedOn('broadcast-queue', BroadcastEvent::class);
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

        $lockKey = 'laravel_unique_job:'.hash('xxh128', TestEventUnique::class).':';
        $this->assertFalse($this->app->get(Cache::class)->lock($lockKey, 10)->get());
    }

    public function testUniqueEventsCanBeBroadcastWithUniqueIdFromProperty()
    {
        Bus::fake();
        Queue::fake();

        Broadcast::queue(new TestEventUniqueWithIdProperty);

        Bus::assertNotDispatched(UniqueBroadcastEvent::class);
        Queue::assertPushed(UniqueBroadcastEvent::class);

        $lockKey = 'laravel_unique_job:'.hash('xxh128', TestEventUniqueWithIdProperty::class).':unique-id-property';
        $this->assertFalse($this->app->get(Cache::class)->lock($lockKey, 10)->get());
    }

    public function testUniqueEventsCanBeBroadcastWithUniqueIdFromMethod()
    {
        Bus::fake();
        Queue::fake();

        Broadcast::queue(new TestEventUniqueWithIdMethod);

        Bus::assertNotDispatched(UniqueBroadcastEvent::class);
        Queue::assertPushed(UniqueBroadcastEvent::class);

        $lockKey = 'laravel_unique_job:'.hash('xxh128', TestEventUniqueWithIdMethod::class).':unique-id-method';
        $this->assertFalse($this->app->get(Cache::class)->lock($lockKey, 10)->get());
    }

    public function testThrowExceptionWhenUnknownStoreIsUsed()
    {
        $this->expectExceptionObject(new InvalidArgumentException('Broadcast connection [alien_connection] is not defined.'));

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

    public function testCustomDriverClosureBoundObjectIsBroadcastManager()
    {
        $manager = new BroadcastManager($this->getApp([
            'broadcasting' => [
                'connections' => [
                    __CLASS__ => [
                        'driver' => __CLASS__,
                    ],
                ],
            ],
        ]));
        $manager->extend(__CLASS__, fn () => $this);
        $this->assertSame($manager, $manager->connection(__CLASS__));
    }

    public function testCustomDriverStaticClosure()
    {
        $manager = new BroadcastManager($this->getApp([
            'broadcasting' => [
                'connections' => [
                    __CLASS__ => [
                        'driver' => __CLASS__,
                    ],
                ],
            ],
        ]));

        $driver = new stdClass;

        $manager->extend(__CLASS__, static fn () => $driver);
        $this->assertSame($driver, $manager->connection(__CLASS__));
    }

    public function testInvokableObjectDriverClosure()
    {
        $manager = new BroadcastManager($this->getApp([
            'broadcasting' => [
                'connections' => [
                    __CLASS__ => [
                        'driver' => __CLASS__,
                    ],
                ],
            ],
        ]));

        $driver = new stdClass;
        $creator = new CustomBroadcastDriver($driver);

        $manager->extend(__CLASS__, $creator(...));
        $this->assertSame($driver, $manager->connection(__CLASS__));
    }

    public function test_throw_exception_when_driver_creation_fails()
    {
        $userConfig = [
            'broadcasting' => [
                'connections' => [
                    'log_connection_1' => [
                        'driver' => 'log',
                    ],
                ],
            ],
        ];

        $app = $this->getApp($userConfig);
        $app->singleton(LoggerInterface::class, function () {
            throw new RuntimeException('Logger service not available');
        });

        $broadcastManager = new BroadcastManager($app);

        try {
            $broadcastManager->connection('log_connection_1');
            $this->fail('Expected BroadcastException was not thrown');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Failed to create broadcaster for connection "log_connection_1"', $e->getMessage());
            $this->assertStringContainsString('Logger service not available', $e->getMessage());
            $this->assertInstanceOf(RuntimeException::class, $e->getPrevious());
        }
    }

    public function testBroadcastManagerCanResolveBackedEnumConnection(): void
    {
        $app = $this->getApp([
            'broadcasting' => [
                'connections' => [
                    'log' => ['driver' => 'log'],
                ],
            ],
        ]);

        $driver = new stdClass;
        $manager = new BroadcastManager($app);
        $manager->extend('log', static fn () => $driver);

        $this->assertSame($driver, $manager->connection(BroadcastConnectionName::Log));
        $this->assertSame($manager->connection('log'), $manager->connection(BroadcastConnectionName::Log));
    }

    public function testBroadcastManagerCanResolveBackedEnumDriver(): void
    {
        $app = $this->getApp([
            'broadcasting' => [
                'connections' => [
                    'log' => ['driver' => 'log'],
                ],
            ],
        ]);

        $driver = new stdClass;
        $manager = new BroadcastManager($app);
        $manager->extend('log', static fn () => $driver);

        $this->assertSame($driver, $manager->driver(BroadcastConnectionName::Log));
        $this->assertSame($manager->driver('log'), $manager->driver(BroadcastConnectionName::Log));
    }

    public function testSetDefaultDriverAcceptsBackedEnum(): void
    {
        $app = $this->getApp([
            'broadcasting' => [
                'default' => 'null',
                'connections' => [],
            ],
        ]);

        $manager = new BroadcastManager($app);
        $manager->setDefaultDriver(BroadcastConnectionName::Log);

        $this->assertSame('log', $app['config']['broadcasting.default']);
    }

    public function testPurgeAcceptsBackedEnum(): void
    {
        $app = $this->getApp([
            'broadcasting' => [
                'connections' => [
                    'log' => ['driver' => 'log'],
                ],
            ],
        ]);

        $manager = new BroadcastManager($app);
        $manager->extend('log', static fn () => new stdClass);

        $instance1 = $manager->connection(BroadcastConnectionName::Log);
        $manager->purge(BroadcastConnectionName::Log);
        $instance2 = $manager->connection(BroadcastConnectionName::Log);

        $this->assertNotSame($instance1, $instance2);
    }

    protected function getApp(array $userConfig)
    {
        $app = new Container;
        $app->singleton('config', fn () => new Repository($userConfig));

        return $app;
    }
}

enum BroadcastConnectionName: string
{
    case Log = 'log';
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

class TestEventUniqueWithIdProperty extends TestEventUnique
{
    public string $uniqueId = 'unique-id-property';
}

class TestEventUniqueWithIdMethod extends TestEventUnique
{
    public string $uniqueId = 'unique-id-method';
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

class CustomBroadcastDriver
{
    public function __construct(private object $driver)
    {
    }

    public function __invoke()
    {
        return $this->driver;
    }
}
