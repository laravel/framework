<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Env;

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
        $app = $this->app ?? new Application;
        $host = Env::get('REDIS_HOST', '127.0.0.1');
        $port = Env::get('REDIS_PORT', 6379);

        if (! extension_loaded('redis')) {
            $this->markTestSkipped('The redis extension is not installed. Please install the extension to enable '.__CLASS__);
        }

        if (static::$connectionFailedOnceWithDefaultsSkip) {
            $this->markTestSkipped('Trying default host/port failed, please set environment variable REDIS_HOST & REDIS_PORT to enable '.__CLASS__);
        }

        foreach ($this->redisDriverProvider() as $driver) {
            $this->redis[$driver[0]] = new RedisManager($app, $driver[0], [
                'cluster' => false,
                'options' => [
                    'prefix' => 'test_',
                ],
                'default' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => 5,
                    'timeout' => 0.5,
                    'name' => 'default',
                ],
            ]);
        }

        try {
            $this->redis['phpredis']->connection()->flushdb();
        } catch (Exception $e) {
            if ($host === '127.0.0.1' && $port === 6379 && Env::get('REDIS_HOST') === null) {
                static::$connectionFailedOnceWithDefaultsSkip = true;
                $this->markTestSkipped('Trying default host/port failed, please set environment variable REDIS_HOST & REDIS_PORT to enable '.__CLASS__);
            }
        }
    }

    /**
     * Teardown redis connection.
     *
     * @return void
     */
    public function tearDownRedis()
    {
        $this->redis['phpredis']->connection()->flushdb();

        foreach ($this->redisDriverProvider() as $driver) {
            $this->redis[$driver[0]]->connection()->disconnect();
        }
    }

    /**
     * Get redis driver provider.
     *
     * @return array
     */
    public function redisDriverProvider()
    {
        return [
            ['predis'],
            ['phpredis'],
        ];
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
