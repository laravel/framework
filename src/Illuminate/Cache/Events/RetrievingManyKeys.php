<?php

namespace Illuminate\Cache\Events;

class RetrievingManyKeys extends CacheEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        ?string $storeName,
        public array $keys,
        array $tags = [],
    ) {
        parent::__construct($storeName, $keys[0] ?? '', $tags);
    }
}
