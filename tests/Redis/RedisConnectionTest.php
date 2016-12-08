<?php

class RedisConnectionTest extends PHPUnit_Framework_TestCase
{
    public function testRedisNotCreateClusterAndOptionsAndClustersServer()
    {
        $redis = $this->getRedis(false);

        $client = $redis->connection('cluster');
        $this->assertNull($client, 'cluster parameter should not create as redis server');

        $client = $redis->connection('options');
        $this->assertNull($client, 'options parameter should not create as redis server');

        $client = $redis->connection('clusters');
        $this->assertNull($client, 'clusters parameter should not create as redis server');
    }

    public function testRedisClusterNotCreateClusterAndOptionsServer()
    {
        $redis = $this->getRedis(true);
        $client = $redis->connection();

        $this->assertCount(1, $client->getConnection());
    }

    public function testRedisClusterCreateMultipleClustersAndNotCreateOptionsServer()
    {
        $redis = $this->getRedis();
        $clusterOne = $redis->connection('cluster-1');
        $clusterTwo = $redis->connection('cluster-2');

        $this->assertCount(1, $clusterOne->getConnection());
        $this->assertCount(1, $clusterTwo->getConnection());

        $client = $redis->connection('options');
        $this->assertNull($client, 'options parameter should not create as redis server');
    }

    protected function getRedis($cluster = false)
    {
        $servers = [
            'cluster' => $cluster,
            'default' => [
                'host'     => '127.0.0.1',
                'port'     => 6379,
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
                        'host'     => '127.0.0.1',
                        'port'     => 6379,
                        'database' => 0,
                    ],
                ],
                'cluster-2' => [
                    [
                        'host'     => '127.0.0.1',
                        'port'     => 6379,
                        'database' => 0,
                    ],
                ],
            ],
        ];

        return new Illuminate\Redis\PredisDatabase($servers);
    }
}
