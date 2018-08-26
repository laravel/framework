<?php

namespace Illuminate\Cache;

use Closure;
use ArrayAccess;
use DateTimeInterface;
use BadMethodCallException;
use Illuminate\Support\Carbon;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Cache\Repository as CacheContract;

/**
 * @mixin \Illuminate\Contracts\Cache\Store
 */
class Repository implements CacheContract, ArrayAccess
{
    use InteractsWithTime;
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

        // If we could not find the cache value, we will fire the missed event and get
        // the default value for this cache value. This default could be a callback
        // so we will execute the value function which will resolve it if needed.
        if (is_null($value)) {
            $this->event(new CacheMissed($key));

            $value = value($default);
        } else {
            $this->event(new CacheHit($key, $value));
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
        $values = $this->store->many(collect($keys)->map(function ($value, $key) {
            return is_string($key) ? $key : $value;
        })->values()->all());

        return collect($values)->map(function ($value, $key) use ($keys) {
            return $this->handleManyResult($keys, $key, $value);
        })->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        if (is_null($default)) {
            return $this->many($keys);
        }

        foreach ($keys as $key) {
            if (! isset($default[$key])) {
                $default[$key] = null;
            }
        }

        return $this->many($default);
    }

    /**
     * Handle a result for the "many" method.
     *
     * @param  array  $keys
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function handleManyResult($keys, $key, $value)
    {
        // If we could not find the cache value, we will fire the missed event and get
        // the default value for this cache value. This default could be a callback
        // so we will execute the value function which will resolve it if needed.
        if (is_null($value)) {
            $this->event(new CacheMissed($key));

            return isset($keys[$key]) ? value($keys[$key]) : null;
        }

        // If we found a valid value we will fire the "hit" event and return the value
        // back from this function. The "hit" event gives developers an opportunity
        // to listen for every possible cache "hit" throughout this applications.
        $this->event(new CacheHit($key, $value));

        return $value;
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
        return tap($this->get($key, $default), function () use ($key) {
            $this->forget($key);
        });
    }

    /**
     * Store an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  \DateTimeInterface|\DateInterval|float|int|null  $minutes
     * @return void
     */
    public function put($key, $value, $minutes = null)
    {
        if (is_array($key)) {
            return $this->putMany($key, $value);
        }

        if (! is_null($minutes = $this->getMinutes($minutes))) {
            $this->store->put($this->itemKey($key), $value, $minutes);

            $this->event(new KeyWritten($key, $value, $minutes));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $this->put($key, $value, $ttl);
    }

    /**
     * Store multiple items in the cache for a given number of minutes.
     *
     * @param  array  $values
     * @param  \DateTimeInterface|\DateInterval|float|int  $minutes
     * @return void
     */
    public function putMany(array $values, $minutes)
    {
        if (! is_null($minutes = $this->getMinutes($minutes))) {
            $this->store->putMany($values, $minutes);

            foreach ($values as $key => $value) {
                $this->event(new KeyWritten($key, $value, $minutes));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        $this->putMany($values, $ttl);
    }

    /**
     * Store an item in the cache if the key does not exist.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  \DateTimeInterface|\DateInterval|float|int  $minutes
     * @return bool
     */
    public function add($key, $value, $minutes)
    {
        if (is_null($minutes = $this->getMinutes($minutes))) {
            return false;
        }

        // If the store has an "add" method we will call the method on the store so it
        // has a chance to override this logic. Some drivers better support the way
        // this operation should work with a total "atomic" implementation of it.
        if (method_exists($this->store, 'add')) {
            return $this->store->add(
                $this->itemKey($key), $value, $minutes
            );
        }

        // If the value did not exist in the cache, we will put the value in the cache
        // so it exists for subsequent requests. Then, we will return true so it is
        // easy to know if the value gets added. Otherwise, we will return false.
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

        $this->event(new KeyWritten($key, $value, 0));
    }

    /**
     * Get an item from the cache, or store the default value.
     *
     * @param  string  $key
     * @param  \DateTimeInterface|\DateInterval|float|int  $minutes
     * @param  \Closure  $callback
     * @return mixed
     */
    public function remember($key, $minutes, Closure $callback)
    {
        $value = $this->get($key);

        // If the item exists in the cache we will just return this immediately and if
        // not we will execute the given Closure and cache the result of that for a
        // given number of minutes so it's available for all subsequent requests.
        if (! is_null($value)) {
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
        $value = $this->get($key);

        // If the item exists in the cache we will just return this immediately and if
        // not we will execute the given Closure and cache the result of that for a
        // given number of minutes so it's available for all subsequent requests.
        if (! is_null($value)) {
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
        return tap($this->store->forget($this->itemKey($key)), function () use ($key) {
            $this->event(new KeyForgotten($key));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        return $this->forget($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->forget($key);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->store->flush();
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
        if (! method_exists($this->store, 'tags')) {
            throw new BadMethodCallException('This cache store does not support tagging.');
        }

        $cache = $this->store->tags(is_array($names) ? $names : func_get_args());

        if (! is_null($this->events)) {
            $cache->setEventDispatcher($this->events);
        }

        return $cache->setDefaultCacheTime($this->default);
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
     * @return $this
     */
    public function setDefaultCacheTime($minutes)
    {
        $this->default = $minutes;

        return $this;
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
     * Fire an event for this cache instance.
     *
     * @param  string  $event
     * @return void
     */
    protected function event($event)
    {
        if (isset($this->events)) {
            $this->events->dispatch($event);
        }
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
     * @param  \DateTimeInterface|\DateInterval|float|int  $duration
     * @return float|int|null
     */
    protected function getMinutes($duration)
    {
        $duration = $this->parseDateInterval($duration);

        if ($duration instanceof DateTimeInterface) {
            $duration = Carbon::now()->diffInSeconds(Carbon::createFromTimestamp($duration->getTimestamp()), false) / 60;
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
