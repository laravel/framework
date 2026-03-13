<?php

namespace Illuminate\Cache\Events;

class WritingKey extends CacheEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        ?string $storeName,
        string $key,
        public mixed $value,
        public ?int $seconds = null,
        array $tags = [],
    ) {
        parent::__construct($storeName, $key, $tags);
    }
}
