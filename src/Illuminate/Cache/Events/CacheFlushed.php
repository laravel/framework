<?php

namespace Illuminate\Cache\Events;

class CacheFlushed extends CacheEvent
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
        $this->storeName = $storeName;
        $this->tags = $tags;
        $this->key = '';
    }
}
