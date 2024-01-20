<?php

namespace Illuminate\Cache\Events;

abstract class CacheEvent
{
    /**
     * The name of the cache.
     *
     * @var string|null
     */
    public $cacheName;

    /**
     * The key of the event.
     *
     * @var string
     */
    public $key;

    /**
     * The tags that were assigned to the key.
     *
     * @var array
     */
    public $tags;

    /**
     * Create a new event instance.
     *
     * @param  string|null  $cacheName
     * @param  string  $key
     * @param  array  $tags
     * @return void
     */
    public function __construct($cacheName, $key, array $tags = [])
    {
        $this->cacheName = $cacheName;
        $this->key = $key;
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
