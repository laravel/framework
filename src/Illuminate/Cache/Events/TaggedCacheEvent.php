<?php

namespace Illuminate\Cache\Events;

interface TaggedCacheEvent
{
    /**
     * Set the tags for the cache event.
     *
     * @param  array  $tags
     * @return $this
     */
    public function setTags($tags);
}
