<?php

namespace Illuminate\Cache;

class ProtectedCache extends Repository
{
    /**
     * The protected key prefix.
     */
    public const PREFIX = '__protected__:';

    /**
     * Format the key for a protected cache item.
     *
     * @param  string  $key
     * @return string
     */
    protected function itemKey($key)
    {
        return static::PREFIX.$key;
    }
}
