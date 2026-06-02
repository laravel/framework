<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Contracts\Redis\Connector;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\RedisManager;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class RedisManagerExtensionTest extends TestCase
{
    /**
     * @var \Illuminate\Redis\RedisManager
     */
    protected $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = new RedisManager(new Application, 'my_custom_driver', [
            'default' => [
                'host' => 'some-host',
                'port' => 'some-port',
                'database' => 5,
                'timeout' => 0.5,
            ],
            'clusters' => [
                'my-cluster' => [
                    [
                        'host' => 'some-host',
                        'port' => 'some-port',
                        'database' => 5,
                        'timeout' => 0.5,
                    ],
                ],
            ],
        ]);

        $this->redis->extend('my_custom_driver', function () {
            return new FakeRedisConnector;
        });
    }

    public function testUsingCustomRedisConnectorWithSingleRedisInstance()
    {
        $this->assertSame(
            'my-redis-connection', $this->redis->resolve()
        );
    }

    public function testUsingCustomRedisConnectorWithRedisClusterInstance()
    {
        $this->assertSame(
            'my-redis-cluster-connection', $this->redis->resolve('my-cluster')
        );
    }

    public function testParseConnectionConfigurationForCluster()
    {
        $name = 'my-cluster';
        $config = [
            [
                'url1',
                'url2',
                'url3',
            ],
        ];
        $redis = new RedisManager(new Application, 'my_custom_driver', [
            'clusters' => [
                $name => $config,
            ],
        ]);
        $redis->extend('my_custom_driver', function () use ($config) {
            return m::mock(Connector::class)
                ->shouldReceive('connectToCluster')
                ->once()
                ->withArgs(function ($configArg) use ($config) {
                    return $config === $configArg;
                })
                ->getMock();
        });

        $redis->resolve($name);
    }

    public function testPurgeAcceptsUnitEnum()
    {
        $redis = new RedisManager(new Application, 'my_custom_driver', [
            'default' => [
                'host' => 'some-host',
                'port' => 'some-port',
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);

        $property = new \ReflectionProperty($redis, 'connections');
        $property->setValue($redis, ['default' => 'fake-connection']);

        $this->assertCount(1, $redis->connections());

        $redis->purge(FakeRedisConnectionName::Default);
        $this->assertCount(0, $redis->connections());
    }

    public function testConnectionIsCachedAfterFirstResolution()
    {
        $connection = new FakeRedisConnection;

        $connector = m::mock(Connector::class);
        $connector->shouldReceive('connect')->once()->andReturn($connection);

        $redis = new RedisManager(new Application, 'my_custom_driver', [
            'default' => [
                'host' => 'some-host',
            ],
        ]);

        $redis->extend('my_custom_driver', fn () => $connector);

        $this->assertSame($connection, $redis->connection());
        $this->assertSame($connection, $redis->connection());
    }

    public function testConnectionSetsEventDispatcherWhenEnabled()
    {
        $connection = new FakeRedisConnection;

        $connector = m::mock(Connector::class);
        $connector->shouldReceive('connect')->once()->andReturn($connection);

        $app = m::mock(Application::class)->makePartial();
        $app->shouldReceive('bound')->once()->with('events')->andReturn(true);
        $app->shouldReceive('make')->once()->with('events')->andReturn(m::mock(Dispatcher::class));

        $redis = new RedisManager($app, 'my_custom_driver', [
            'default' => [
                'host' => 'some-host',
            ],
        ]);

        $redis->extend('my_custom_driver', fn () => $connector);
        $redis->enableEvents();

        $redis->connection();

        $this->assertSame('default', $connection->getName());
        $this->assertInstanceOf(Dispatcher::class, $connection->getEventDispatcher());
    }

    public function testResolveThrowsWhenConnectionIsNotConfigured()
    {
        $redis = new RedisManager(new Application, 'my_custom_driver', []);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Redis connection [missing] not configured.');

        $redis->resolve('missing');
    }

    public function testResolveUsesPerConnectionParameters()
    {
        $connector = m::mock(Connector::class);
        $connector->shouldReceive('connect')->once()->with(
            ['host' => 'some-host'],
            ['foo' => 'bar', 'parameters' => ['timeout' => 1]]
        )->andReturn('my-redis-connection');

        $redis = new RedisManager(new Application, 'my_custom_driver', [
            'options' => [
                'foo' => 'bar',
                'parameters' => [
                    'default' => [
                        'timeout' => 1,
                    ],
                ],
            ],
            'default' => [
                'host' => 'some-host',
            ],
        ]);

        $redis->extend('my_custom_driver', fn () => $connector);

        $this->assertSame('my-redis-connection', $redis->resolve());
    }
}

class FakeRedisConnection extends Connection
{
    public function createSubscription($channels, \Closure $callback, $method = 'subscribe')
    {
        //
    }
}

class FakeRedisConnector implements Connector
{
    /**
     * Create a new clustered Predis connection.
     *
     * @param  array  $config
     * @param  array  $options
     * @return string
     */
    public function connect(array $config, array $options)
    {
        return 'my-redis-connection';
    }

    /**
     * Create a new clustered Predis connection.
     *
     * @param  array  $config
     * @param  array  $clusterOptions
     * @param  array  $options
     * @return string
     */
    public function connectToCluster(array $config, array $clusterOptions, array $options)
    {
        return 'my-redis-cluster-connection';
    }
}

enum FakeRedisConnectionName: string
{
    case Default = 'default';
}
