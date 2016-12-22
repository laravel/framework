<?php

namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Redis\Database
 * @see \Illuminate\Redis\PredisDatabase
 * @see \Illuminate\Redis\PhpRedisDatabase
 * @see \Illuminate\Contracts\Redis\Database
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
