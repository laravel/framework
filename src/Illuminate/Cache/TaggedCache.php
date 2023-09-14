<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Store;

class TaggedCache extends Repository
{
    /**
     * The array to track cache keys associated with tags.
     *
     * @var array
     */
    protected $taggedCacheKeys = [];

    use RetrievesMultipleKeys {
        putMany as putManyAlias;
    }

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
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param  array  $values
     * @param  int|null  $ttl
     * @return bool
     */
    public function putMany(array $values, $ttl = null)
    {
        if ($ttl === null) {
            return $this->putManyForever($values);
        }

        return $this->putManyAlias($values, $ttl);
    }

    /**
     * Store an item in the cache.
     *
     * This method was added to keep track of the cache keys associated
     * with their respective tags.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  \DateTimeInterface|\DateInterval|float|int|null  $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = null): bool
    {
        $result = parent::set($key, $value, $ttl);

        foreach ($this->tags->getNames() as $tag) {
            $this->taggedCacheKeys[$tag][] = $key;
            $this->taggedCacheKeys[$tag] = array_unique($this->taggedCacheKeys[$tag]);
        }

        return $result;
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        return $this->store->increment($this->itemKey($key), $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        return $this->store->decrement($this->itemKey($key), $value);
    }

    /**
     * Remove all items from the cache.
     *
     * This method was modified to ensure that all tagged cache items
     * are cleared out when the flush method is called.
     *
     * @return bool
     */
    public function flush()
    {
        foreach ($this->tags->getNames() as $tag) {
            if (isset($this->taggedCacheKeys[$tag])) {
                foreach ($this->taggedCacheKeys[$tag] as $key) {
                    parent::forget($key);
                }
                unset($this->taggedCacheKeys[$tag]);
            }
        }

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
     * @param  \Illuminate\Cache\Events\CacheEvent  $event
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
