<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Contracts\Cache\Repository  store(string|null $name = null)
 *
 * @see \Illuminate\Cache\CacheManager
 * @see \Illuminate\Cache\Repository
 */
class Cache extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cache';
    }
}
