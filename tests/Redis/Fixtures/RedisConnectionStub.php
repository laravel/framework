<?php

namespace Illuminate\Tests\Redis\Fixtures;

use Illuminate\Redis\Connections\Connection;

/**
 * Declares the Redis commands that Connection otherwise only forwards through __call(), so they can be doubled.
 */
class RedisConnectionStub extends Connection
{
    public function __construct()
    {
        //
    }

    public function createSubscription($channels, \Closure $callback, $method = 'subscribe')
    {
        //
    }

    public function get(...$arguments)
    {
        //
    }

    public function mget(...$arguments)
    {
        //
    }

    public function set(...$arguments)
    {
        //
    }

    public function setex(...$arguments)
    {
        //
    }

    public function incrby(...$arguments)
    {
        //
    }

    public function decrby(...$arguments)
    {
        //
    }

    public function expire(...$arguments)
    {
        //
    }

    public function del(...$arguments)
    {
        //
    }

    public function flushdb(...$arguments)
    {
        //
    }

    public function lrange(...$arguments)
    {
        //
    }

    public function pipeline(...$arguments)
    {
        //
    }

    public function transaction(...$arguments)
    {
        //
    }

    public function zrange(...$arguments)
    {
        //
    }

    public function exec(...$arguments)
    {
        //
    }

    public function multi(...$arguments)
    {
        //
    }

    public function keys(...$arguments)
    {
        //
    }

    public function eval(...$arguments)
    {
        //
    }

    public function exists(...$arguments)
    {
        //
    }

    public function ttl(...$arguments)
    {
        //
    }

    public function lpush(...$arguments)
    {
        //
    }

    public function rpush(...$arguments)
    {
        //
    }

    public function zadd(...$arguments)
    {
        //
    }

    public function zrem(...$arguments)
    {
        //
    }

    public function zcard(...$arguments)
    {
        //
    }

    public function llen(...$arguments)
    {
        //
    }
}
