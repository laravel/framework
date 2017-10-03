<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Redis\Connections\Connection connection(string | null $name) Get a Redis connection by name.
 * @method static \Illuminate\Redis\Connections\Connection resolve(string | null $name) Resolve the given connection by name.
 * @method static array connections() Return all of the created connections.
 *
 * @see \Illuminate\Redis\RedisManager
 * @see \Illuminate\Contracts\Redis\Factory
 */
class Redis extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'redis';
    }
}
