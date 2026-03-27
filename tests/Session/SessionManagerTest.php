<?php

namespace Illuminate\Tests\Session;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Session\CacheBasedSessionHandler;
use Illuminate\Session\SessionManager;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SessionManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        Container::setInstance(null);
    }

    public function testCacheBasedSessionHandlerSharesCacheRepository()
    {
        $app = $this->createApplication('memcached');

        $manager = new SessionManager($app);
        $session = $manager->driver('memcached');

        $handler = $session->getHandler();

        $this->assertInstanceOf(CacheBasedSessionHandler::class, $handler);

        // The handler should use the same Repository instance, not a clone
        $this->assertSame(
            $app->make('cache')->store('memcached'),
            $handler->getCache()
        );
    }

    public function testRedisSessionWithoutConnectionSharesCacheRepository()
    {
        $app = $this->createApplication('redis');

        $manager = new SessionManager($app);
        $session = $manager->driver('redis');

        $handler = $session->getHandler();

        $this->assertInstanceOf(CacheBasedSessionHandler::class, $handler);

        // Without session.connection, the handler should share the cache Repository
        $this->assertSame(
            $app->make('cache')->store('redis'),
            $handler->getCache()
        );
    }

    public function testRedisSessionWithConnectionDoesNotMutateSharedStore()
    {
        $app = $this->createApplication('redis', 'session');

        $sharedStore = $app->make('cache')->store('redis')->getStore();
        $originalConnection = (new \ReflectionProperty($sharedStore, 'connection'))->getValue($sharedStore);

        $manager = new SessionManager($app);
        $session = $manager->driver('redis');

        $handler = $session->getHandler();

        // The shared cache store's connection should not be mutated
        $currentConnection = (new \ReflectionProperty($sharedStore, 'connection'))->getValue($sharedStore);
        $this->assertSame($originalConnection, $currentConnection);

        // The session handler's store should have the session connection
        $sessionStore = $handler->getCache()->getStore();
        $sessionConnection = (new \ReflectionProperty($sessionStore, 'connection'))->getValue($sessionStore);
        $this->assertSame('session', $sessionConnection);
    }

    protected function createApplication(string $driver, ?string $sessionConnection = null): Container
    {
        $app = new Container;
        Container::setInstance($app);

        $config = new Repository([
            'session' => [
                'driver' => $driver,
                'lifetime' => 120,
                'connection' => $sessionConnection,
                'store' => null,
            ],
            'cache' => [
                'default' => $driver,
                'stores' => [
                    'memcached' => ['driver' => 'array'],
                    'redis' => ['driver' => 'redis', 'connection' => 'default'],
                ],
                'prefix' => 'test',
            ],
        ]);

        $app->instance('config', $config);
        $app->singleton('cache', function ($app) {
            return new \Illuminate\Cache\CacheManager($app);
        });

        $app->singleton('redis', function () {
            $redis = m::mock(\Illuminate\Contracts\Redis\Factory::class);
            $redis->shouldReceive('connection')->andReturn(
                m::mock(\Illuminate\Redis\Connections\Connection::class)
            );

            return $redis;
        });

        return $app;
    }
}
