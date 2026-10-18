<?php

namespace Illuminate\Tests\Integration\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\MercureBroadcaster;
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
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\NullQueue;
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

    public function testEventsCanBeBroadcastWhenForwardingQueue()
    {
        Bus::fake();
        Queue::fake();

        Queue::forward('broadcast-queue', 'events', 'broadcast-connection');

        Broadcast::queue(new TestForwardedEvent);
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

    public function testUniqueEventsUseInheritedUniqueForAttribute()
    {
        Queue::fake();

        Broadcast::queue(new TestEventUniqueWithInheritedUniqueFor);
        Broadcast::queue(new TestEventUniqueWithInheritedUniqueFor);

        Queue::assertPushed(UniqueBroadcastEvent::class, 1);

        $this->travel(60)->seconds();

        Broadcast::queue(new TestEventUniqueWithInheritedUniqueFor);

        Queue::assertPushed(UniqueBroadcastEvent::class, 2);
    }

    public function testUniqueForAttributeDoesNotMakeEventsUniqueWithoutTheContract()
    {
        Queue::fake();

        Broadcast::queue(new TestEventWithUniqueForAttribute);
        Broadcast::queue(new TestEventWithUniqueForAttribute);

        Queue::assertPushed(BroadcastEvent::class, 2);
        Queue::assertNotPushed(UniqueBroadcastEvent::class);
    }

    public function testUniqueEventLockIsReleasedWhenPushingToTheQueueFails()
    {
        Queue::extend('failing', fn () => new class implements ConnectorInterface
        {
            public function connect(array $config)
            {
                return new class extends NullQueue
                {
                    public function push($job, $data = '', $queue = null)
                    {
                        throw new RuntimeException('Queue unavailable.');
                    }
                };
            }
        });

        $this->app['config']->set('queue.connections.failing', ['driver' => 'failing']);

        try {
            Broadcast::queue(new TestEventUniqueOnFailingConnection);

            $this->fail('Expected pushing the broadcast event to fail.');
        } catch (RuntimeException $e) {
            $this->assertSame('Queue unavailable.', $e->getMessage());
        }

        $lockKey = 'laravel_unique_job:'.hash('xxh128', TestEventUniqueOnFailingConnection::class).':';

        $this->assertTrue($this->app->get(Cache::class)->lock($lockKey, 10)->get());
    }

    public function testRescuedUniqueEventLockIsReleasedWhenPushingToTheQueueFails()
    {
        Queue::extend('failing', fn () => new class implements ConnectorInterface
        {
            public function connect(array $config)
            {
                return new class extends NullQueue
                {
                    public function push($job, $data = '', $queue = null)
                    {
                        throw new RuntimeException('Queue unavailable.');
                    }
                };
            }
        });

        $this->app['config']->set('queue.connections.failing', ['driver' => 'failing']);

        Broadcast::queue(new TestEventUniqueRescuedOnFailingConnection);

        $lockKey = 'laravel_unique_job:'.hash('xxh128', TestEventUniqueRescuedOnFailingConnection::class).':';

        $this->assertTrue($this->app->get(Cache::class)->lock($lockKey, 10)->get());
    }

    public function testUniqueEventLockIsReleasedFromCustomCacheWhenPushingToTheQueueFails()
    {
        Queue::extend('failing', fn () => new class implements ConnectorInterface
        {
            public function connect(array $config)
            {
                return new class extends NullQueue
                {
                    public function push($job, $data = '', $queue = null)
                    {
                        throw new RuntimeException('Queue unavailable.');
                    }
                };
            }
        });

        $this->app['config']->set('queue.connections.failing', ['driver' => 'failing']);
        $this->app['config']->set('cache.stores.unique', ['driver' => 'array']);

        try {
            Broadcast::queue(new TestEventUniqueWithCustomCacheOnFailingConnection);

            $this->fail('Expected pushing the broadcast event to fail.');
        } catch (RuntimeException $e) {
            $this->assertSame('Queue unavailable.', $e->getMessage());
        }

        $lockKey = 'laravel_unique_job:'.hash('xxh128', TestEventUniqueWithCustomCacheOnFailingConnection::class).':';

        $this->assertTrue($this->app['cache']->store('unique')->lock($lockKey, 10)->get());
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

    public function testMercureWithoutAUrlResolvesOutsideFrankenPhp()
    {
        $manager = new BroadcastManager($this->getApp([
            'broadcasting' => ['connections' => ['mercure' => ['driver' => 'mercure', 'secret' => str_repeat('s', 32)]]],
        ]));

        $broadcaster = $manager->connection('mercure');

        $this->assertInstanceOf(MercureBroadcaster::class, $broadcaster);

        $this->expectException(BroadcastException::class);
        $this->expectExceptionMessage('"url"');

        $broadcaster->broadcast(['chat'], 'message');
    }

    public function testAsyncMercureRequiresAUrl()
    {
        $manager = new BroadcastManager($this->getApp([
            'broadcasting' => ['connections' => ['mercure' => ['driver' => 'mercure', 'secret' => str_repeat('s', 32), 'async' => true]]],
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('"async"');

        $manager->connection('mercure');
    }

    public function testMercureEventsAreBroadcastNow()
    {
        Bus::fake();
        Queue::fake();

        config([
            'broadcasting.default' => 'mercure',
            'broadcasting.connections.mercure' => $this->mercureConfig(['driver' => 'mercure']),
        ]);

        Broadcast::queue(new TestEvent);

        Bus::assertDispatched(BroadcastEvent::class);
        Queue::assertNotPushed(BroadcastEvent::class);
    }

    public function testAsyncMercureEventsAreQueued()
    {
        Bus::fake();
        Queue::fake();

        config([
            'broadcasting.default' => 'mercure',
            'broadcasting.connections.mercure' => $this->mercureConfig(['driver' => 'mercure', 'async' => true]),
        ]);

        Broadcast::queue(new TestEvent);

        Bus::assertNotDispatched(BroadcastEvent::class);
        Queue::assertPushed(BroadcastEvent::class);
    }

    public function testEventsBroadcastToMercureAndOtherConnectionsAreQueued()
    {
        Bus::fake();
        Queue::fake();

        config([
            'broadcasting.default' => 'mercure',
            'broadcasting.connections.mercure' => $this->mercureConfig(['driver' => 'mercure']),
            'broadcasting.connections.log' => ['driver' => 'log'],
        ]);

        Broadcast::queue(new TestEventOnMercureAndLog);

        Bus::assertNotDispatched(BroadcastEvent::class);
        Queue::assertPushed(BroadcastEvent::class);
    }

    public function testMercureRequiresASecret()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"secret"');

        (new BroadcastManager($this->getApp([])))->mercure(['url' => 'https://hub.test/.well-known/mercure']);
    }

    public function testMercureRejectsAShortHmacSecret()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at least 32 bytes');

        (new BroadcastManager($this->getApp([])))->mercure($this->mercureConfig(['secret' => 'too-short']));
    }

    public function testMercureRejectsANegativePublishExpiration()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('publish_expiration');

        (new BroadcastManager($this->getApp([])))->mercure($this->mercureConfig(['publish_expiration' => -1]));
    }

    public function testMercureRejectsAPublishExpirationTruncatingToZeroSeconds()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('publish_expiration');

        (new BroadcastManager($this->getApp([])))->mercure($this->mercureConfig(['publish_expiration' => 0.01]));
    }

    public function testMercureAcceptsASubMinutePublishExpiration()
    {
        $hub = (new BroadcastManager($this->getApp([])))->mercure($this->mercureConfig(['publish_expiration' => 0.5]));

        $this->assertNotNull($hub->getProvider()->getJwt());
    }

    public function testMercureRejectsANonPositiveSubscribeExpiration()
    {
        $manager = new BroadcastManager($this->getApp([
            'broadcasting' => ['connections' => ['mercure' => $this->mercureConfig(['driver' => 'mercure', 'subscribe_expiration' => 0])]],
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('subscribe_expiration');

        $manager->connection('mercure');
    }

    public function testMercureRejectsASubscribeExpirationTruncatingToZeroSeconds()
    {
        $manager = new BroadcastManager($this->getApp([
            'broadcasting' => ['connections' => ['mercure' => $this->mercureConfig(['driver' => 'mercure', 'subscribe_expiration' => 0.01])]],
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('subscribe_expiration');

        $manager->connection('mercure');
    }

    public function testMercureAcceptsASubMinuteSubscribeExpiration()
    {
        $manager = new BroadcastManager($this->getApp([
            'broadcasting' => ['connections' => ['mercure' => $this->mercureConfig(['driver' => 'mercure', 'subscribe_expiration' => 0.5])]],
        ]));

        $this->assertInstanceOf(MercureBroadcaster::class, $manager->connection('mercure'));
    }

    public function testMercureRejectsAMalformedEncryptionKey()
    {
        $manager = new BroadcastManager($this->getApp([
            'broadcasting' => ['connections' => ['mercure' => $this->mercureConfig(['driver' => 'mercure', 'encryption_key' => 'not-a-valid-key'])]],
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('encryption_key');

        $manager->connection('mercure');
    }

    public function testMercureRejectsASecurePrefixedCookieOverAPlainHttpPublicUrl()
    {
        $manager = new BroadcastManager($this->getApp([
            'broadcasting' => ['connections' => ['mercure' => $this->mercureConfig([
                'driver' => 'mercure',
                'public_url' => 'http://localhost/.well-known/mercure',
            ])]],
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cookie_name');

        $manager->connection('mercure');
    }

    public function testMercureAcceptsAPlainHttpPublicUrlWithAnUnprefixedCookieName()
    {
        $manager = new BroadcastManager($this->getApp([
            'broadcasting' => ['connections' => ['mercure' => $this->mercureConfig([
                'driver' => 'mercure',
                'public_url' => 'http://localhost/.well-known/mercure',
                'cookie_name' => 'mercureAuthorization',
            ])]],
        ]));

        $this->assertInstanceOf(MercureBroadcaster::class, $manager->connection('mercure'));
    }

    public function testMercureAcceptsABase64PrefixedEncryptionKey()
    {
        $manager = new BroadcastManager($this->getApp([
            'broadcasting' => ['connections' => ['mercure' => $this->mercureConfig([
                'driver' => 'mercure',
                'encryption_key' => 'base64:'.base64_encode(random_bytes(32)),
            ])]],
        ]));

        $this->assertInstanceOf(MercureBroadcaster::class, $manager->connection('mercure'));
    }

    public function testMercureDefaultsTheRfc9068Claims()
    {
        $manager = new BroadcastManager($this->getApp(['app' => ['url' => 'https://app.test']]));

        $hub = $manager->mercure($this->mercureConfig());

        $claims = $this->decodeJwtClaims($hub->getFactory()->create());

        $this->assertSame('https://app.test', $claims['iss']);
        $this->assertSame('https://app.test', $claims['client_id']);
        $this->assertSame('https://hub.test/.well-known/mercure', $claims['aud']);
        $this->assertSame('anonymous', $claims['sub']);

        $publishClaims = $this->decodeJwtClaims($hub->getProvider()->getJwt());

        $this->assertSame('https://app.test', $publishClaims['iss']);
        $this->assertSame('https://app.test', $publishClaims['client_id']);
    }

    public function testMercureExplicitClaimsWinOverTheDefaults()
    {
        $manager = new BroadcastManager($this->getApp(['app' => ['url' => 'https://app.test']]));

        $hub = $manager->mercure($this->mercureConfig([
            'claims' => ['iss' => 'https://issuer.test', 'aud' => 'https://audience.test', 'client_id' => 'my-app'],
        ]));

        $claims = $this->decodeJwtClaims($hub->getFactory()->create());

        $this->assertSame('https://issuer.test', $claims['iss']);
        $this->assertSame('https://audience.test', $claims['aud']);
        $this->assertSame('my-app', $claims['client_id']);
    }

    public function testMercureSideSpecificSecretsTakePrecedence()
    {
        $manager = new BroadcastManager($this->getApp([]));

        $hub = $manager->mercure($this->mercureConfig([
            'subscribe_secret' => str_repeat('a', 32),
            'publish_secret' => str_repeat('b', 32),
        ]));

        $this->assertJwtSignedWith($hub->getFactory()->create(), str_repeat('a', 32));
        $this->assertJwtSignedWith($hub->getProvider()->getJwt(), str_repeat('b', 32));
    }

    protected function mercureConfig(array $overrides = [])
    {
        return $overrides + [
            'url' => 'https://hub.test/.well-known/mercure',
            'secret' => str_repeat('s', 32),
        ];
    }

    protected function decodeJwtClaims(string $jwt): array
    {
        $payload = explode('.', $jwt)[1];

        return json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
    }

    protected function assertJwtSignedWith(string $jwt, string $secret): void
    {
        [$header, $payload, $signature] = explode('.', $jwt);

        $this->assertSame(
            rtrim(strtr(base64_encode(hash_hmac('sha256', $header.'.'.$payload, $secret, true)), '+/', '-_'), '='),
            $signature
        );
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

class TestForwardedEvent implements ShouldBroadcast
{
    public $queue = 'broadcast-queue';

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

class TestEventOnMercureAndLog extends TestEvent
{
    public function broadcastConnections()
    {
        return ['mercure', 'log'];
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

#[UniqueFor(60)]
class TestEventWithUniqueForAttribute extends TestEvent
{
}

class TestEventUniqueWithInheritedUniqueFor extends TestEventWithUniqueForAttribute implements ShouldBeUnique
{
}

class TestEventUniqueOnFailingConnection extends TestEventUnique
{
    public $connection = 'failing';
}

class TestEventUniqueRescuedOnFailingConnection extends TestEventUniqueOnFailingConnection implements ShouldRescue
{
}

class TestEventUniqueWithCustomCacheOnFailingConnection extends TestEventUniqueOnFailingConnection
{
    public function uniqueVia(): Cache
    {
        return Container::getInstance()->make('cache')->store('unique');
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
