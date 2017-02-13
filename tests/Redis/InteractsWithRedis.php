<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Redis\RedisManager;

trait InteractsWithRedis
{
    /**
     * @var bool
     */
    private static $connectionFailedOnceWithDefaultsSkip = false;

    /**
     * @var RedisManager[]
     */
    private $redis;

    public function setUpRedis()
    {
        $host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = getenv('REDIS_PORT') ?: 6379;

        if (static::$connectionFailedOnceWithDefaultsSkip) {
            $this->markTestSkipped('Trying default host/port failed, please set environment variable REDIS_HOST & REDIS_PORT to enable '.__CLASS__);

            return;
        }

        foreach (['predis', 'phpredis'] as $driver) {
            $this->redis[$driver] = new RedisManager($driver, [
                'cluster' => false,
                'default' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => 5,
                    'timeout' => 0.5,
                ],
            ]);
        }

        try {
            $this->redis['predis']->connection()->flushdb();
        } catch (\Exception $e) {
            if ($host === '127.0.0.1' && $port === 6379 && getenv('REDIS_HOST') === false) {
                $this->markTestSkipped('Trying default host/port failed, please set environment variable REDIS_HOST & REDIS_PORT to enable '.__CLASS__);
                static::$connectionFailedOnceWithDefaultsSkip = true;

                return;
            }
        }
    }

    public function tearDownRedis()
    {
        if ($this->redis) {
            $this->redis['predis']->connection()->flushdb();
            $this->redis['predis']->connection()->disconnect();
            $this->redis['phpredis']->connection()->close();
        }
    }

    public function redisDriverProvider()
    {
        return [
            ['predis'],
            ['phpredis'],
        ];
    }
}
