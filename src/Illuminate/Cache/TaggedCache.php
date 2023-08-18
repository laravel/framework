<?php

namespace Illuminate\Cache;

use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Contracts\Cache\Store;

class TaggedCache extends Repository
{
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
     * List of keys within this namespace.
     *
     * @var array
     */
    protected $itemKeys = [];

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
     * @return bool
     */
    public function flush()
    {
        foreach ($this->getItemKeys() as $key) {
            $this->store->forget($key);
        }
        $this->store->forget($this->getMetadataKey());
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
        $itemKey = $this->itemKey($event->key);
        if ($itemKey !== $this->getMetadataKey() && ($event instanceof KeyWritten || $event instanceof KeyForgotten)) {
            $itemKeys = $this->getItemKeys();
            if ($event instanceof KeyWritten && !in_array($itemKey, $itemKeys)) {
                $itemKeys[] = $itemKey;
            } elseif ($event instanceof KeyForgotten && in_array($itemKey, $this->itemKeys)) {
                $itemKeys = array_values(
                    array_filter($itemKeys, function ($k) use ($itemKey) {
                        return $k !== $itemKey;
                    })
                );
            }
            $this->putItemKeys($itemKeys);
        }
        parent::event($event->setTags($this->tags->getNames()));
    }

    private function getMetadataKey(): string
    {
        return $this->taggedItemKey('meta:keys');
    }

    private function getItemKeys(): array
    {
        $metadataKey = $this->getMetadataKey();
        $keys = $this->store->get($metadataKey);
        if (! is_array($keys)) {
            $keys = [];
        }
        return $keys;
    }

    private function putItemKeys(array $keys): void
    {
        $metadataKey = $this->getMetadataKey();
        $this->store->forever($metadataKey, $keys);
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
