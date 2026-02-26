<?php

namespace Illuminate\Cache\Events;

class CacheLocksFlushFailed
{
    /**
     * The name of the cache store.
     *
     * @var string|null
     */
    public ?string $storeName;

    /**
     * Create a new event instance.
     *
     * @param  string|null  $storeName
     */
    public function __construct(?string $storeName)
    {
        $this->storeName = $storeName;
    }
}
