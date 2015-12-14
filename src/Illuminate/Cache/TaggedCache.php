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
     * {@inheritdoc}
     */
    protected function fireCacheEvent($event, $payload)
    {
        if (preg_match('/^'.sha1($this->tags->getNamespace()).':(.*)$/', $payload[0], $matches) === 1) {
            $payload[0] = $matches[1];
        }
        $payload[] = $this->tags->getNames();

        parent::fireCacheEvent($event, $payload);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        return parent::get($this->taggedItemKey($key), $default);
    }

    /**
     * {@inheritdoc}
     */
    public function put($key, $value, $minutes)
    {
        parent::put($this->taggedItemKey($key), $value, $minutes);
    }

    /**
     * {@inheritdoc}
     */
    public function add($key, $value, $minutes)
    {
        if (method_exists($this->store, 'add')) {
            $key = $this->taggedItemKey($key);
        }

        return parent::add($key, $value, $minutes);
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function increment($key, $value = 1)
    {
        $this->store->increment($this->taggedItemKey($key), $value);
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function decrement($key, $value = 1)
    {
        $this->store->decrement($this->taggedItemKey($key), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function forever($key, $value)
    {
        parent::forever($this->taggedItemKey($key), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function forget($key)
    {
        return parent::forget($this->taggedItemKey($key));
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public function flush()
    {
        $this->tags->reset();
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
}
