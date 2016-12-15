<?php

use Illuminate\Redis\PhpRedisDatabase;

class PhpRedisConnectionTest extends PHPUnit_Framework_TestCase
{
    public function testPhpRedisNotCreateClusterAndOptionsAndClustersServer()
    {
        $redis = $this->getRedis();

        $client = $redis->connection('cluster');
        $this->assertNull($client, 'cluster parameter should not create as redis server');

        $client = $redis->connection('options');
        $this->assertNull($client, 'options parameter should not create as redis server');

        $client = $redis->connection('clusters');
        $this->assertNull($client, 'clusters parameter should not create as redis server');
    }

    public function testPhpRedisClusterNotCreateClusterAndOptionsServer()
    {
        $redis = $this->getRedis();
        $this->assertEquals(['default', 'cluster-1', 'cluster-2'], array_keys($redis->clients));
    }

    public function testPhpRedisClusterCreateMultipleClustersAndNotCreateOptionsServer()
    {
        $redis = $this->getRedis();

        $clusterOne = $redis->connection('cluster-1');
        $clusterTwo = $redis->connection('cluster-2');

        $this->assertInstanceOf(RedisClusterStub::class, $clusterOne);
        $this->assertInstanceOf(RedisClusterStub::class, $clusterTwo);

        $client = $redis->connection('options');
        $this->assertNull($client, 'options parameter should not create as redis server');
    }

    protected function getRedis()
    {
        $servers = [
            'default' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 0,
            ],
            'options' => [
                'prefix' => 'prefix:',
            ],
            'clusters' => [
                'options' => [
                    'prefix' => 'cluster:',
                ],
                'cluster-1' => [
                    [
                        'host' => '127.0.0.1',
                        'port' => 6379,
                        'database' => 0,
                    ],
                ],
                'cluster-2' => [
                    [
                        'host' => '127.0.0.1',
                        'port' => 6379,
                        'database' => 0,
                    ],
                ],
            ],
        ];

        return new PhpRedisDatabaseStub($servers);
    }
}

class PhpRedisDatabaseStub extends PhpRedisDatabase
{
    protected function createRedisClusterInstance(array $servers, array $options)
    {
        return new RedisClusterStub();
    }

    protected function createRedisInstance(array $server, array $options)
    {
        return new RedisStub;
    }
}

class RedisStub
{
}

class RedisClusterStub
{
}
