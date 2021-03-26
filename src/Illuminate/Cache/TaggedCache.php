<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Store;

class TaggedCache extends Repository
{
    /**
     * The tag set instance.
     *
     * @var \Illuminate\Cache\TagSet
     */
    protected $tags;

    /**
     * Create a new tagged cache instance.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @param  \Illuminate\Cache\TagSet  $tags
     * @return void
     */
    public function __construct(Store $store, TagSet $tags)
    {
        parent::__construct($store);

        $this->tags = $tags;
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        $this->tags->reset();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function itemKey($key)
    {
        return $this->taggedItemKey($key);
    }

    /**
     * Get a fully qualified key for a tagged item.
     *
     * @param  string  $key
     * @return string
     */
    public function taggedItemKey($key)
    {
        return sha1($this->tags->getNamespace()).':'.$key;
    }

    /**
     * Fire an event for this cache instance.
     *
     * @param  string  $event
     * @return void
     */
    protected function event($event)
    {
        parent::event($event->setTags($this->tags->getNames()));
    }

    /**
     * Get the tag set instance.
     *
     * @return \Illuminate\Cache\TagSet
     */
    public function getTags()
    {
        return $this->tags;
    }
}
