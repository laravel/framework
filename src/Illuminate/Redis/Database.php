<?php

namespace Illuminate\Redis;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Redis\Database as DatabaseContract;

abstract class Database implements DatabaseContract
{
    /**
     * Get a specific Redis connection instance.
     *
     * @param string $name
     *
     * @return \Predis\ClientInterface|\RedisCluster|\Redis|null
     */
    public function connection($name = 'default')
    {
        return Arr::get($this->clients, $name ?: 'default');
    }

    /**
     * Run a command against the Redis database.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function command($method, array $parameters = [])
    {
        return call_user_func_array([$this->clients['default'], $method], $parameters);
    }

    /**
     * Dynamically make a Redis command.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->command($method, $parameters);
    }
}
