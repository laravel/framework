<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Contracts\Redis\Connector;
use Illuminate\Foundation\Application;
use Illuminate\Redis\RedisManager;
use Mockery;
use PHPUnit\Framework\TestCase;

class RedisManagerExtensionTest extends TestCase
{
    /**
     * @var \Illuminate\Redis\RedisManager
     */
    protected $redis;

    protected function setUp(): void
    {
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
            'sentinels' => [
                'my-sentinel' => [
                    'hosts' => [['host' => 'sentinel-1', 'port' => 26379]],
                    'service' => 'mymaster',
                    'database' => 5,
                    'timeout' => 0.5,
                ],
            ],
        ]);

        $this->redis->extend('my_custom_driver', function () {
            return new FakeRedisConnector;
        });
    }

    public function testUsingCustomRedisConnectorWithRedisSentinelInstance()
    {
        $this->assertSame(
            'my-redis-sentinel-connection', $this->redis->resolve('my-sentinel')
        );
    }

    public function testParseConnectionConfigurationForSentinel()
    {
        $connector = new FakeRedisConnector;

        $redis = new RedisManager(new Application, 'my_custom_driver', [
            'options' => [
                'prefix' => 'laravel_database_',
            ],
            'sentinels' => [
                'my-sentinel' => [
                    'hosts' => [['host' => 'sentinel-1', 'port' => 26379]],
                    'service' => 'mymaster',
                    'database' => 5,
                ],
            ],
        ]);

        $redis->extend('my_custom_driver', function () use ($connector) {
            return $connector;
        });

        $redis->resolve('my-sentinel');

        $this->assertSame([
            'hosts' => [['host' => 'sentinel-1', 'port' => 26379]],
            'service' => 'mymaster',
            'database' => 5,
        ], $connector->sentinelConfig);

        $this->assertSame(['prefix' => 'laravel_database_'], $connector->sentinelOptions);
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
            return Mockery::mock(Connector::class)
                ->expects('connectToCluster')
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
}

class FakeRedisConnector implements Connector
{
    public $sentinelConfig;

    public $sentinelOptions;

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

    /**
     * Create a new Sentinel connection.
     *
     * @param  array  $config
     * @param  array  $options
     * @return string
     */
    public function connectToSentinel(array $config, array $options)
    {
        $this->sentinelConfig = $config;
        $this->sentinelOptions = $options;

        return 'my-redis-sentinel-connection';
    }
}

enum FakeRedisConnectionName: string
{
    case Default = 'default';
}
