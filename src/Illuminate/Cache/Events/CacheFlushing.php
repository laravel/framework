<?php

namespace Illuminate\Cache\Events;

class CacheFlushing implements TaggedCacheEvent
{
    /**
     * The name of the cache store.
     *
     * @var string|null
     */
    public $storeName;

    /**
     * The tags that were assigned to the cache event.
     *
     * @var array
     */
    public $tags;

    /**
     * Create a new event instance.
     *
     * @param  string|null  $storeName
     * @param  array  $tags
     */
    public function __construct($storeName, array $tags = [])
    {
        $this->storeName = $storeName;
        $this->tags = $tags;
    }

    /**
     * Set the tags for the cache event.
     *
     * @param  array  $tags
     * @return $this
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }
}
