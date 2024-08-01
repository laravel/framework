<?php

namespace Illuminate\Support\Facades;

/**
 * @mixin \Illuminate\Hashing\HashManager
 * @mixin \Illuminate\Hashing\AbstractHasher
 */
class Hash extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'hash';
    }
}
