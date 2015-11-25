<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Store;

class XCacheStore extends TaggableStore implements Store
{
    /**
     * A string that should be prepended to keys.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Create a new WinCache store.
     *
     * @param string $prefix
     */
    public function __construct($prefix = '')
    {
        $this->prefix = $prefix;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $value = xcache_get($this->prefix.$key);

        if (isset($value)) {
            return $value;
        }
    }

    /**
     * Retrieve multiple items from the cache by key,
     * items not found in the cache will have a null value for the key.
     *
     * @param string[] $keys
     *
     * @return array
     */
    public function getMulti(array $keys)
    {
        $returnValues = [];

        foreach ($keys as $singleKey) {
            $returnValues[$singleKey] = $this->get($singleKey);
        }

        return $returnValues;
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $minutes
     */
    public function put($key, $value, $minutes)
    {
        xcache_set($this->prefix.$key, $value, $minutes * 60);
    }

    /**
     * Store multiple items in the cache for a set number of minutes.
     *
     * @param array $values  array of key => value pairs
     * @param int   $minutes
     */
    public function putMulti(array $values, $minutes)
    {
        foreach ($values as $key => $singleValue) {
            $this->put($key, $singleValue, $minutes);
        }
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return int
     */
    public function increment($key, $value = 1)
    {
        return xcache_inc($this->prefix.$key, $value);
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        return xcache_dec($this->prefix.$key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, 0);
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     *
     * @return bool
     */
    public function forget($key)
    {
        return xcache_unset($this->prefix.$key);
    }

    /**
     * Remove all items from the cache.
     */
    public function flush()
    {
        xcache_clear_cache(XC_TYPE_VAR);
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
}
