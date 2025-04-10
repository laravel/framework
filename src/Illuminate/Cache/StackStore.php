<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\RetrievesTTL;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StackStore implements Store
{
    /**
     * @param  Collection<int, Repository>  $stack
     */
    public function __construct(
        protected Collection $stack
    ) {
        $supported = $this->stack->every(
            fn ($repository) => $repository->getStore() instanceof RetrievesTTL
        );

        if (! $supported) {
            throw new RuntimeException('All stores in the stack must support retrieving TTLs.');
        }
    }
    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        foreach ($this->stack as $index => $repository) {
            $timestamp = now()->getTimestamp();
            $value = $repository->get($key);

            if ($value === null) {
                continue;
            }

            $ttl = $repository->ttlInSeconds($key);

            break;
        }

        if ($value === null) {
            return null;
        }

        foreach ($this->stack->take($index) as $repository) {
            $repository->put($key, $value, $ttl);
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
        //
    }

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        //
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param  array  $values
     * @param  int  $seconds
     * @return bool
     */
    public function putMany(array $values, $seconds)
    {
        //
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
        //
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
        //
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever($key, $value)
    {
        //
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        //
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        //
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        //
    }
}
