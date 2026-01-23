<?php

namespace Illuminate\Tests\Session;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Session\CacheBasedSessionHandler;
use Illuminate\Session\SessionManager;
use PHPUnit\Framework\TestCase;

class SessionManagerTest extends TestCase
{
    public function test_cache_based_session_handler_uses_cached_repository_instance()
    {
        $app = new Container;
        $app->singleton('config', function () {
            return new Repository([
                'session' => [
                    'driver' => 'array',
                    'lifetime' => 120,
                    'store' => 'array',
                ],
                'cache' => [
                    'default' => 'array',
                    'stores' => [
                        'array' => [
                            'driver' => 'array',
                        ],
                    ],
                ],
            ]);
        });
        $app->singleton('cache', fn ($app) => new CacheManager($app));

        $manager = new class($app) extends SessionManager
        {
            public function createCacheHandlerPublic($driver)
            {
                return $this->createCacheHandler($driver);
            }
        };

        $cacheRepository = $app->make('cache')->store('array');

        $handler = $manager->createCacheHandlerPublic('array');

        $this->assertSame($cacheRepository, $handler->getCache());
    }

    public function test_redis_session_connection_does_not_mutate_cache_store_connection()
    {
        $app = new Container;
        $app->singleton('config', function () {
            return new Repository([
                'session' => [
                    'driver' => 'redis',
                    'lifetime' => 120,
                    'store' => null,
                    'connection' => 'session',
                ],
                'cache' => [
                    'default' => 'redis',
                    'stores' => [
                        'redis' => [
                            'driver' => 'redis',
                            'connection' => 'cache',
                        ],
                    ],
                ],
            ]);
        });

        $cacheManager = new CacheManager($app);
        $cacheManager->extend('redis', function ($app, $config) {
            return new CacheRepository(new FakeRedisStore($config['connection'] ?? 'default'));
        });
        $app->instance('cache', $cacheManager);

        $manager = new SessionManager($app);

        $cacheRepository = $cacheManager->store('redis');
        $this->assertSame('cache', $cacheRepository->getStore()->getConnection());

        $sessionStore = $manager->driver('redis');
        $handler = $sessionStore->getHandler();

        $this->assertInstanceOf(CacheBasedSessionHandler::class, $handler);
        $this->assertSame('session', $handler->getCache()->getStore()->getConnection());
        $this->assertSame('cache', $cacheRepository->getStore()->getConnection());
        $this->assertNotSame($cacheRepository, $handler->getCache());
    }

    public function test_redis_session_reuses_connection_for_same_label()
    {
        $app = new Container;
        $app->singleton('config', function () {
            return new Repository([
                'session' => [
                    'driver' => 'redis',
                    'lifetime' => 120,
                    'store' => null,
                    'connection' => 'cache',
                ],
                'cache' => [
                    'default' => 'redis',
                    'stores' => [
                        'redis' => [
                            'driver' => 'redis',
                            'connection' => 'cache',
                        ],
                    ],
                ],
            ]);
        });

        $fakeRedis = new FakeRedisFactory;
        $app->instance('redis', $fakeRedis);
        $app->singleton('cache', fn ($app) => new CacheManager($app));

        $manager = new SessionManager($app);

        $cacheRepository = $app->make('cache')->store('redis');
        $cacheConnection = $cacheRepository->getStore()->connection();

        $sessionStore = $manager->driver('redis');
        $handler = $sessionStore->getHandler();
        $sessionConnection = $handler->getCache()->getStore()->connection();

        $this->assertSame($cacheConnection, $sessionConnection);
        $this->assertSame(1, $fakeRedis->connectionCount('cache'));
    }
}

class FakeRedisStore extends ArrayStore
{
    protected $connection;

    public function __construct($connection)
    {
        parent::__construct();

        $this->connection = $connection;
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}

class FakeRedisFactory implements \Illuminate\Contracts\Redis\Factory
{
    protected $connections = [];
    protected $connectionCounts = [];

    public function connection($name = null)
    {
        $name = $name ?: 'default';

        if (! isset($this->connections[$name])) {
            $this->connections[$name] = new FakeRedisConnection($name);
            $this->connectionCounts[$name] = ($this->connectionCounts[$name] ?? 0) + 1;
        }

        return $this->connections[$name];
    }

    public function connectionCount($name)
    {
        return $this->connectionCounts[$name] ?? 0;
    }
}

class FakeRedisConnection extends Connection
{
    public function __construct($name)
    {
        $this->setName($name);
    }

    public function createSubscription($channels, \Closure $callback, $method = 'subscribe')
    {
        // No-op for test.
    }
}
