<?php

namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Hashing\Hasher
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
