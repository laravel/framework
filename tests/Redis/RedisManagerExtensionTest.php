<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Contracts\Redis\Connector;
use Illuminate\Foundation\Application;
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

    protected function tearDown(): void
    {
        m::close();
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

    public function test_parse_connection_configuration_for_cluster()
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

    public function testResolvesToClusterWhenClusterEnabled()
    {
        $app = new Application;
        $clusterConfig = [
            'default' => [
                'host' => 'default-host',
                'port' => 'default-port',
                'database' => 0,
                'timeout' => 0.5,
            ],
            'clusters' => [
                'default' => [
                    [
                        'host' => 'cluster-host-1',
                        'port' => 'cluster-port-2',
                        'database' => 0,
                        'timeout' => 0.5,
                    ],
                    [
                        'host' => 'cluster-host-2',
                        'port' => 'cluster-port-3',
                        'database' => 0,
                        'timeout' => 0.5,
                    ],
                ],
            ],
            'options' => [
                'cluster' => 'redis',
                'prefix' => 'laravel_database_',
            ],
            'cluster_enabled' => true,
        ];

        $redis = new RedisManager($app, 'predis', $clusterConfig);

        $redis->extend('predis', function () {
            $mock = m::mock(Connector::class);
            $mock->shouldReceive('connect')->andReturn('single-connection');
            $mock->shouldReceive('connectToCluster')->andReturn('cluster-connection');

            return $mock;
        });

        // Assert that the manager resolves to a cluster connection when cluster_enabled is true
        $this->assertSame('cluster-connection', $redis->resolve());
    }

    public function testResolvesToSingleConnectionWhenClusterIsDefinedButDisabled()
    {
        $app = new Application;
        $config = [
            'default' => [
                'host' => 'default-host',
                'port' => 'default-port',
                'database' => 0,
                'timeout' => 0.5,
            ],
            'clusters' => [
                'default' => [
                    [
                        'host' => 'cluster-host',
                        'port' => 'cluster-port',
                        'database' => 0,
                        'timeout' => 0.5,
                    ],
                ],
            ],
            'options' => [
                'cluster' => 'redis',
                'prefix' => 'laravel_database_',
            ],
            'cluster_enabled' => false, // Explicitly set to false
        ];

        $redis = new RedisManager($app, 'predis', $config);

        $redis->extend('predis', function () {
            $mock = m::mock(Connector::class);
            $mock->shouldReceive('connect')->andReturn('single-connection');
            $mock->shouldReceive('connectToCluster')->andReturn('cluster-connection');

            return $mock;
        });

        // Assert that the manager resolves to a single connection when cluster_enabled is false
        $this->assertSame('single-connection', $redis->resolve());
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
