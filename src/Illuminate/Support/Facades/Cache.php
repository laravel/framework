<?php

namespace Illuminate\Support\Facades;

/**
 * @mixin \Illuminate\Cache\CacheManager
 * @mixin \Illuminate\Cache\Repository
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
