<?php

namespace Illuminate\Cache\Events;

class CacheFailedOver
{
    /**
     * Create a new event instance.
     *
     * @param  string  $storeName  The name of the cache store that failed.
     */
    public function __construct(
        public ?string $storeName,
    ) {
    }
}
