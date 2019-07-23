<?php

namespace Illuminate\Tests\Redis;

use PHPUnit\Framework\TestCase;
use Illuminate\Redis\RedisManager;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Redis\Connector;

class RedisManagerExtensionTest extends TestCase
{
    /**
     * Redis manager instance.
     *
     * @var RedisManager
     */
    protected $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = new RedisManager(new Application(), 'my_custom_driver', [
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
            return new FakeRedisConnnector();
        });
    }

    public function test_using_custom_redis_connector_with_single_redis_instance()
    {
        $this->assertEquals(
            'my-redis-connection', $this->redis->resolve()
        );
    }

    public function test_using_custom_redis_connector_with_redis_cluster_instance()
    {
        $this->assertEquals(
            'my-redis-cluster-connection', $this->redis->resolve('my-cluster')
        );
    }
}

class FakeRedisConnnector implements Connector
{
    /**
     * Create a new clustered Predis connection.
     *
     * @param array $config
     * @param array $options
     * @return \Illuminate\Contracts\Redis\Connection
     */
    public function connect(array $config, array $options)
    {
        return 'my-redis-connection';
    }

    /**
     * Create a new clustered Predis connection.
     *
     * @param array $config
     * @param array $clusterOptions
     * @param array $options
     * @return \Illuminate\Contracts\Redis\Connection
     */
    public function connectToCluster(array $config, array $clusterOptions, array $options)
    {
        return 'my-redis-cluster-connection';
    }
}
