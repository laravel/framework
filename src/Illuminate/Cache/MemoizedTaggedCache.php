<?php

namespace Illuminate\Cache;

class MemoizedTaggedCache extends TaggedCache
{
    /**
     * The memoized cache values.
     *
     * @var array<string, mixed>
     */
    protected $cache = [];

    /**
     * The tagged store instance.
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected TaggedCache $taggedCache;

    public function __construct(TaggableStore $store, TagSet $tags)
    {
        $this->taggedCache = $store->tags($tags->getNames());

        parent::__construct($store, $tags);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null): mixed
    {
        $prefixedKey = $this->itemKey($key);

        if (array_key_exists($prefixedKey, $this->cache)) {
            return $this->cache[$prefixedKey];
        }

        return $this->cache[$prefixedKey] = $this->taggedCache->get($key, $default);
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * @param  array  $defaults
     * @return array
     */
    public function many(array $defaults)
    {
        $memoized = [];
        $missing = [];

        // Check which keys are already memoized
        foreach ($defaults as $key => $value) {
            $prefixedKey = $this->itemKey($key);

            if (array_key_exists($prefixedKey, $this->cache)) {
                $memoized[$key] = $this->cache[$prefixedKey];
            } else {
                $missing[] = $key;
            }
        }

        // Fetch missing keys from the parent TaggedCache
        if (! empty($missing)) {
            $retrieved = $this->taggedCache->many($missing);

            // Memoize the retrieved values
            foreach ($retrieved as $key => $value) {
                $this->cache[$this->itemKey($key)] = $value;
            }

            $memoized = array_merge($memoized, $retrieved);
        }

        // Ensure the result matches the order of the requested keys
        $result = [];
        foreach ($defaults as $key => $value) {
            $result[$key] = $memoized[$key] ?? $value;
        }

        return $result;
    }

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $ttl
     * @return bool
     */
    public function put($key, $value, $ttl = null)
    {
        unset($this->cache[$this->itemKey($key)]);

        return $this->taggedCache->put($key, $value, $ttl);
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param  int  $seconds
     * @return bool
     */
    public function putMany(array $values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            unset($this->cache[$this->itemKey($key)]);
        }

        return $this->taggedCache->putMany($values, $ttl);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        unset($this->cache[$this->itemKey($key)]);

        return $this->taggedCache->forget($key);
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        $this->cache = [];

        return $this->taggedCache->flush();
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
        unset($this->cache[$this->itemKey($key)]);

        return $this->taggedCache->increment($key, $value);
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
        unset($this->cache[$this->itemKey($key)]);

        return $this->taggedCache->decrement($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function itemKey($key)
    {
        return $this->taggedItemKey($this->getPrefix().$key);
    }
}
