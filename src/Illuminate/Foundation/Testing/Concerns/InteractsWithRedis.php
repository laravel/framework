<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Redis\RedisManager;

trait InteractsWithRedis
{
    /**
     * Indicate connection failed if redis is not available.
     *
     * @var bool
     */
    private static $connectionFailedOnceWithDefaultsSkip = false;

    /**
     * Redis manager instance.
     *
     * @var \Illuminate\Redis\RedisManager[]
     */
    private $redis;

    /**
     * Setup redis connection.
     *
     * @return void
     */
    public function setUpRedis()
    {
        if (static::$connectionFailedOnceWithDefaultsSkip) {
            $this->markTestSkipped('Trying default host/port failed, please set environment variable REDIS_HOST & REDIS_PORT to enable '.__CLASS__);

            return;
        }

        $this->configureConnections();

        try {
            $this->redis['predis']->connection()->flushdb();
        } catch (\Exception $e) {
            $host = getenv('REDIS_HOST') ?: '127.0.0.1';
            $port = getenv('REDIS_PORT') ?: 6379;

            if ($host === '127.0.0.1' && $port === 6379 && getenv('REDIS_HOST') === false) {
                $this->markTestSkipped('Trying default host/port failed, please set environment variable REDIS_HOST & REDIS_PORT to enable '.__CLASS__);
                static::$connectionFailedOnceWithDefaultsSkip = true;

                return;
            }
        }
    }

    /**
     * Configure private redis connections with added prefix configurations.
     *
     * @return void
     */
    protected function configureConnections()
    {
        if (! $this->redis) {
            $host = getenv('REDIS_HOST') ?: '127.0.0.1';
            $port = getenv('REDIS_PORT') ?: 6379;

            foreach ($this->redisDriverProvider() as $driver) {
                $this->redis[$driver[0]] = new RedisManager($driver[0], [
                    'cluster' => false,
                    'default' => [
                        'host' => $host,
                        'port' => $port,
                        'database' => 5,
                        'timeout' => 0.5,
                    ],
                ]);

                $this->redis["{$driver[0]}-prefixed"] = new RedisManager($driver[0], [
                    'cluster' => false,
                    'default' => [
                        'host' => $host,
                        'port' => $port,
                        'options' => ['prefix' => 'laravel:'],
                        'database' => 5,
                        'timeout' => 0.5,
                    ],
                ]);
            }
        }
    }

    /**
     * Returns the connections for use as a dataProvider.
     *
     * @return array
     */
    public function redisConnections()
    {
        $connections = [];
        $this->configureConnections();

        foreach ($this->redis as $driver => $redis) {
            $connections[$driver] = [$redis->connection()];
        }

        return $connections;
    }

    /**
     * Teardown redis connection.
     *
     * @return void
     */
    public function tearDownRedis()
    {
        $this->redis['predis']->connection()->flushdb();

        foreach ($this->redis as $driver) {
            $driver->connection()->disconnect();
        }
    }

    /**
     * Get redis driver provider.
     *
     * @return array
     */
    public function redisDriverProvider()
    {
        $providers = [
            ['predis'],
        ];

        if (extension_loaded('redis')) {
            $providers[] = ['phpredis'];
        }

        return $providers;
    }

    /**
     * Run test if redis is available.
     *
     * @param  callable  $callback
     * @return void
     */
    public function ifRedisAvailable($callback)
    {
        $this->setUpRedis();

        $callback();

        $this->tearDownRedis();
    }
}
