<?php

namespace Illuminate\Cache\Events;

class CacheLocksFlushFailed
{
    /**
     * Create a new event instance.
     *
     * @param  string|null  $storeName
     */
    public function __construct(
        public ?string $storeName,
    ) {}
}
