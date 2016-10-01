<?php

namespace Illuminate\Cache;

use Closure;
use DateTime;
use ArrayAccess;
use Carbon\Carbon;
use BadMethodCallException;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Cache\Repository as CacheContract;

class Repository implements CacheContract, ArrayAccess
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The cache store implementation.
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $store;

    /**
     * The event dispatcher implementation.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The default number of minutes to store items.
     *
     * @var float|int
     */
    protected $default = 60;

    /**
     * Create a new cache repository instance.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @return void
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function setEventDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Fire an event for this cache instance.
     *
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    protected function fireCacheEvent($event, $payload)
    {
        if (! isset($this->events)) {
            return;
        }

        switch ($event) {
            case 'hit':
                if (count($payload) == 2) {
                    $payload[] = [];
                }

                return $this->events->fire(new Events\CacheHit($payload[0], $payload[1], $payload[2]));
            case 'missed':
                if (count($payload) == 1) {
                    $payload[] = [];
                }

                return $this->events->fire(new Events\CacheMissed($payload[0], $payload[1]));
            case 'delete':
                if (count($payload) == 1) {
                    $payload[] = [];
                }

                return $this->events->fire(new Events\KeyForgotten($payload[0], $payload[1]));
            case 'write':
                if (count($payload) == 3) {
                    $payload[] = [];
                }

                return $this->events->fire(new Events\KeyWritten($payload[0], $payload[1], $payload[2], $payload[3]));
        }
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return ! is_null($this->get($key));
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->many($key);
        }

        $value = $this->store->get($this->itemKey($key));

        if (is_null($value)) {
            $this->fireCacheEvent('missed', [$key]);

            $value = value($default);
        } else {
            $this->fireCacheEvent('hit', [$key, $value]);
        }

        return $value;
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
        $normalizedKeys = [];

        foreach ($keys as $key => $value) {
            $normalizedKeys[] = is_string($key) ? $key : $value;
        }

        $values = $this->store->many($normalizedKeys);

        foreach ($values as $key => &$value) {
            if (is_null($value)) {
                $this->fireCacheEvent('missed', [$key]);

                $value = isset($keys[$key]) ? value($keys[$key]) : null;
            } else {
                $this->fireCacheEvent('hit', [$key, $value]);
            }
        }

        return $values;
    }

    /**
     * Retrieve an item from the cache and delete it.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        $value = $this->get($key, $default);

        $this->forget($key);

        return $value;
    }

    /**
     * Store an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  \DateTime|float|int  $minutes
     * @return void
     */
    public function put($key, $value, $minutes = null)
    {
        if (is_array($key)) {
            return $this->putMany($key, $value);
        }

        $minutes = $this->getMinutes($minutes);

        if (! is_null($minutes)) {
            $this->store->put($this->itemKey($key), $value, $minutes);

            $this->fireCacheEvent('write', [$key, $value, $minutes]);
        }
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
        $minutes = $this->getMinutes($minutes);

        if (! is_null($minutes)) {
            $this->store->putMany($values, $minutes);

            foreach ($values as $key => $value) {
                $this->fireCacheEvent('write', [$key, $value, $minutes]);
            }
        }
    }

    /**
     * Store an item in the cache if the key does not exist.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  \DateTime|float|int  $minutes
     * @return bool
     */
    public function add($key, $value, $minutes)
    {
        $minutes = $this->getMinutes($minutes);

        if (is_null($minutes)) {
            return false;
        }

        if (method_exists($this->store, 'add')) {
            return $this->store->add($this->itemKey($key), $value, $minutes);
        }

        if (is_null($this->get($key))) {
            $this->put($key, $value, $minutes);

            return true;
        }

        return false;
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
        return $this->store->increment($key, $value);
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
        return $this->store->decrement($key, $value);
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
        $this->store->forever($this->itemKey($key), $value);

        $this->fireCacheEvent('write', [$key, $value, 0]);
    }

    /**
     * Get an item from the cache, or store the default value.
     *
     * @param  string  $key
     * @param  \DateTime|float|int  $minutes
     * @param  \Closure  $callback
     * @return mixed
     */
    public function remember($key, $minutes, Closure $callback)
    {
        // If the item exists in the cache we will just return this immediately
        // otherwise we will execute the given Closure and cache the result
        // of that execution for the given number of minutes in storage.
        if (! is_null($value = $this->get($key))) {
            return $value;
        }

        $this->put($key, $value = $callback(), $minutes);

        return $value;
    }

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * @param  string   $key
     * @param  \Closure  $callback
     * @return mixed
     */
    public function sear($key, Closure $callback)
    {
        return $this->rememberForever($key, $callback);
    }

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * @param  string   $key
     * @param  \Closure  $callback
     * @return mixed
     */
    public function rememberForever($key, Closure $callback)
    {
        // If the item exists in the cache we will just return this immediately
        // otherwise we will execute the given Closure and cache the result
        // of that execution for the given number of minutes. It's easy.
        if (! is_null($value = $this->get($key))) {
            return $value;
        }

        $this->forever($key, $value = $callback());

        return $value;
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        $success = $this->store->forget($this->itemKey($key));

        $this->fireCacheEvent('delete', [$key]);

        return $success;
    }

    /**
     * Begin executing a new tags operation if the store supports it.
     *
     * @param  array|mixed  $names
     * @return \Illuminate\Cache\TaggedCache
     *
     * @throws \BadMethodCallException
     */
    public function tags($names)
    {
        if (method_exists($this->store, 'tags')) {
            $taggedCache = $this->store->tags($names);

            if (! is_null($this->events)) {
                $taggedCache->setEventDispatcher($this->events);
            }

            $taggedCache->setDefaultCacheTime($this->default);

            return $taggedCache;
        }

        throw new BadMethodCallException('This cache store does not support tagging.');
    }

    /**
     * Format the key for a cache item.
     *
     * @param  string  $key
     * @return string
     */
    protected function itemKey($key)
    {
        return $key;
    }

    /**
     * Get the default cache time.
     *
     * @return float|int
     */
    public function getDefaultCacheTime()
    {
        return $this->default;
    }

    /**
     * Set the default cache time in minutes.
     *
     * @param  float|int  $minutes
     * @return void
     */
    public function setDefaultCacheTime($minutes)
    {
        $this->default = $minutes;
    }

    /**
     * Get the cache store implementation.
     *
     * @return \Illuminate\Contracts\Cache\Store
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Determine if a cached value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Store an item in the cache for the default time.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->put($key, $value, $this->default);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->forget($key);
    }

    /**
     * Calculate the number of minutes with the given duration.
     *
     * @param  \DateTime|float|int  $duration
     * @return float|int|null
     */
    protected function getMinutes($duration)
    {
        if ($duration instanceof DateTime) {
            $duration = Carbon::now()->diffInSeconds(Carbon::instance($duration), false) / 60;
        }

        return (int) ($duration * 60) > 0 ? $duration : null;
    }

    /**
     * Handle dynamic calls into macros or pass missing methods to the store.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->store->$method(...$parameters);
    }

    /**
     * Clone cache repository instance.
     *
     * @return void
     */
    public function __clone()
    {
        $this->store = clone $this->store;
    }
}
