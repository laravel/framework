<?php

namespace Illuminate\Cache\Events;

class CacheFlushing extends CacheEvent
{
    /**
     * Create a new event instance.
     *
     * @param  string|null  $storeName
     * @param  array  $tags
     * @return void
     */
    public function __construct($storeName, array $tags = [])
    {
        parent::__construct($storeName, '', $tags);
    }
}
