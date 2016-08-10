<?php

class RedisConnectionTest extends PHPUnit_Framework_TestCase
{
    public function testRedisNotCreateClusterAndOptionsServer()
    {
        $redis = $this->getRedis(false);

        $client = $redis->connection('cluster');
        $this->assertNull($client, 'cluster parameter should not create as redis server');

        $client = $redis->connection('options');
        $this->assertNull($client, 'options parameter should not create as redis server');
    }

    public function testRedisClusterNotCreateClusterAndOptionsServer()
    {
        $redis = $this->getRedis(true);
        $client = $redis->connection();

        $this->assertCount(1, $client->getConnection());
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
        ];

        return new Illuminate\Redis\Database($servers);
    }
}
