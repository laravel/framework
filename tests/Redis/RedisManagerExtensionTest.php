<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Contracts\Redis\Connector;
use Illuminate\Foundation\Application;
use Illuminate\Redis\RedisManager;
use InvalidArgumentException;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use PHPUnit\Framework\TestCase;

class RedisManagerExtensionTest extends TestCase
{
    use VerifiesDoubles;

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

    public function testCustomClientCanBeSelectedForAnIndividualConnection()
    {
        $redis = new RedisManager(new Application, 'default-client', [
            'default' => 'redis://localhost:6379',
            'queue' => ['client' => 'queue-client', 'host' => 'queue-host'],
        ]);

        $redis->extend('default-client', function () {
            return new FakeRedisConnector;
        });

        $connector = Double::for(Connector::class);
        $connector->expects('connect')->with(['host' => 'queue-host'], ['parameters' => []])->returns('queue-connection');

        $redis->extend('queue-client', function () use ($connector) {
            return $connector;
        });

        $this->assertSame('queue-connection', $redis->resolve('queue'));
        $this->assertSame('my-redis-connection', $redis->resolve());
    }

    public function testCustomClientCanBeSelectedForAnIndividualCluster()
    {
        $redis = new RedisManager(new Application, 'phpredis', [
            'options' => ['prefix' => 'global:'],
            'clusters' => [
                'options' => ['cluster' => 'redis'],
                'queue' => [
                    'redis://localhost:6380',
                    'options' => ['client' => 'cluster-client', 'prefix' => 'queue:'],
                ],
            ],
        ]);

        $connector = Double::for(Connector::class);
        $connector->expects('connectToCluster')->with([
            ['host' => 'localhost', 'port' => 6380, 'scheme' => 'tcp'],
            'options' => ['prefix' => 'queue:'],
        ], ['cluster' => 'redis'], ['prefix' => 'global:'])->returns('queue-cluster');

        $redis->extend('cluster-client', function () use ($connector) {
            return $connector;
        });

        $this->assertSame('queue-cluster', $redis->resolve('queue'));
    }

    public function testUnsupportedConnectionClientThrowsAnException()
    {
        $redis = new RedisManager(new Application, 'phpredis', [
            'queue' => ['client' => 'unknown-client'],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Redis client [unknown-client] for connection [queue] is not supported.');

        $redis->resolve('queue');
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
            $connector = Double::for(Connector::class);
            $connector->expects('connectToCluster')->with($config, Argument::remaining());

            return $connector;
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
