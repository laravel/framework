<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Redis\Connections\Connection connection(string|null $name = null)
 * @method static array connections()
 * @method static void disableEvents()
 * @method static void enableEvents()
 * @method static \Illuminate\Redis\RedisManager extend(string $driver, \Closure $callback)
 * @method static void purge(string|null $name = null)
 * @method static \Illuminate\Redis\Connections\Connection resolve(string|null $name = null)
 * @method static void setDriver(string $driver)
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
