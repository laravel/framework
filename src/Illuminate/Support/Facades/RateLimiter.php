<?php

namespace Illuminate\Support\Facades;

class RateLimiter extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Illuminate\Cache\RateLimiter';
    }
}
