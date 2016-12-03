<?php

namespace Illuminate\Cache;

use Memcached;
use Carbon\Carbon;
use ReflectionMethod;
use Illuminate\Contracts\Cache\Store;

class MemcachedStore extends TaggableStore implements Store
{
    /**
     * The Memcached instance.
     *
     * @var \Memcached
     */
    protected $memcached;

    /**
     * A string that should be prepended to keys.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Indicates whether we are using Memcached version >= 3.0.0.
     *
     * @var bool
     */
    protected $onVersionThree;

    /**
     * Create a new Memcached store.
     *
     * @param  \Memcached  $memcached
     * @param  string      $prefix
     * @return void
     */
    public function __construct($memcached, $prefix = '')
    {
        $this->setPrefix($prefix);
        $this->memcached = $memcached;

        $this->onVersionThree = (new ReflectionMethod('Memcached', 'getMulti'))
                            ->getNumberOfParameters() == 2;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key)
    {
        $value = $this->memcached->get($this->prefix.$key);

        if ($this->memcached->getResultCode() == 0) {
            return $value;
        }
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys)
    {
        $prefixedKeys = array_map(function ($key) {
            return $this->prefix.$key;
        }, $keys);

        if ($this->onVersionThree) {
            $values = $this->memcached->getMulti($prefixedKeys, Memcached::GET_PRESERVE_ORDER);
        } else {
            $null = null;

            $values = $this->memcached->getMulti($prefixedKeys, $null, Memcached::GET_PRESERVE_ORDER);
        }

        if ($this->memcached->getResultCode() != 0) {
            return array_fill_keys($keys, null);
        }

        return array_combine($keys, $values);
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  float|int  $minutes
     * @return void
     */
    public function put($key, $value, $minutes)
    {
        $this->memcached->set($this->prefix.$key, $value, $this->toTimestamp($minutes));
    }

    /**
     * Store multiple items in the cache for a given number of minutes.
     *
     * @param  array  $values
     * @param  float|int  $minutes
     * @return void
     */
    public function putMany(array $values, $minutes)
    {
        $prefixedValues = [];

        foreach ($values as $key => $value) {
            $prefixedValues[$this->prefix.$key] = $value;
        }

        $this->memcached->setMulti($prefixedValues, $this->toTimestamp($minutes));
    }

    /**
     * Store an item in the cache if the key doesn't exist.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  float|int  $minutes
     * @return bool
     */
    public function add($key, $value, $minutes)
    {
        return $this->memcached->add($this->prefix.$key, $value, $this->toTimestamp($minutes));
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        return $this->memcached->increment($this->prefix.$key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        return $this->memcached->decrement($this->prefix.$key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function forever($key, $value)
    {
        $this->put($key, $value, 0);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        return $this->memcached->delete($this->prefix.$key);
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public function flush()
    {
        $this->memcached->flush();
    }

    /**
     * Get the UNIX timestamp for the given number of minutes.
     *
     * @parma  int  $minutes
     * @return int
     */
    protected function toTimestamp($minutes)
    {
        return $minutes > 0 ? Carbon::now()->addMinutes($minutes)->getTimestamp() : 0;
    }

    /**
     * Get the underlying Memcached connection.
     *
     * @return \Memcached
     */
    public function getMemcached()
    {
        return $this->memcached;
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

    /**
     * Set the cache key prefix.
     *
     * @param  string  $prefix
     * @return void
     */
    public function setPrefix($prefix)
    {
        $this->prefix = ! empty($prefix) ? $prefix.':' : '';
    }
}
