<?php

namespace Illuminate\Cache\Events;

class CacheFlushFailed
{
    /**
     * Create a new event instance.
     *
     * @param  string|null  $storeName
     * @param  array  $tags
     */
    public function __construct(
        public ?string $storeName,
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
