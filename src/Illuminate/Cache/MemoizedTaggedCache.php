<?php

namespace Illuminate\Cache;

use function Illuminate\Support\enum_value;

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
        if (is_array($key)) {
            return $this->many($key);
        }

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
        $keys = [];
        $memoized = [];
        $missing = [];

        foreach ($defaults as $key => $value) {
            $key = array_is_list($defaults) ? enum_value($value) : enum_value($key);
            $keys[$key] = array_is_list($defaults) ? null : $value;
            $prefixedKey = $this->itemKey($key);

            if (array_key_exists($prefixedKey, $this->cache)) {
                $memoized[$key] = $this->cache[$prefixedKey];
            } else {
                $missing[] = $key;
            }
        }

        if (! empty($missing)) {
            $retrieved = $this->taggedCache->many($missing);

            foreach ($retrieved as $key => $value) {
                $this->cache[$this->itemKey($key)] = $value;
            }

            $memoized = array_merge($memoized, $retrieved);
        }

        $result = [];
        foreach ($keys as $key => $default) {
            $result[$key] = array_key_exists($key, $memoized) && ! is_null($memoized[$key])
                ? $memoized[$key]
                : value($default);
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
