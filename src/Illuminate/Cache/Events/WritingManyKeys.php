<?php

namespace Illuminate\Cache\Events;

class WritingManyKeys extends CacheEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        ?string $storeName,
        public array $keys,
        public mixed $values,
        public ?int $seconds = null,
        array $tags = [],
    ) {
        parent::__construct($storeName, $keys[0], $tags);
    }
}
