<?php

namespace Illuminate\Cache\Events;

abstract class CacheEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public ?string $storeName,
        public string $key,
        public array $tags = [],
    ) {
    }

    /**
     * Set the tags for the cache event.
     *
     * @param  array  $tags
     * @return $this
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;

        return $this;
    }
}
