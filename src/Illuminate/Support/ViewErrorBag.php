<?php

namespace Illuminate\Support;

use Countable;
use Illuminate\Contracts\Support\MessageBag as MessageBagContract;

class ViewErrorBag implements Countable
{
    /**
     * The array of the view error bags.
     *
     * @var array
     */
    protected $bags = [];

    /**
     * Checks if a named MessageBag exists in the bags.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasBag($key = 'default')
    {
        return isset($this->bags[$key]);
    }

    /**
     * Get a MessageBag instance from the bags.
     *
     * @param  string  $key
     * @return \Illuminate\Contracts\Support\MessageBag
     */
    public function getBag($key)
    {
        return Arr::get($this->bags, $key) ?: new MessageBag;
    }

    /**
     * Get all the bags.
     *
     * @return array
     */
    public function getBags()
    {
        return $this->bags;
    }

    /**
     * Add a new MessageBag instance to the bags.
     *
     * @param  string  $key
     * @param  \Illuminate\Contracts\Support\MessageBag  $bag
     * @return $this
     */
    public function put($key, MessageBagContract $bag)
    {
        $this->bags[$key] = $bag;

        return $this;
    }

    /**
     * Determine if the default message bag has any messages.
     *
     * @return bool
     */
    public function any()
    {
        return $this->count() > 0;
    }

    /**
     * Get the number of messages in the default bag.
     *
     * @return int
     */
    public function count()
    {
        return $this->getBag('default')->count();
    }

    /**
     * Determine if messages exist for all of the given keys in any MessageBag.
     *
     * @param  array|string  $key
     * @return bool
     */
    public function anyBagHas($key)
    {
        $keys = is_array($key) ? $key : func_get_args();
        $messageKeys = [];

        foreach ($this->bags as $bag) {
            $messageKeys = array_merge($messageKeys, $bag->keys());
        }

        return count($keys) == count(array_intersect($keys, $messageKeys));
    }

    /**
     * Determine if messages exist for any of the given keys in any MessageBag.
     *
     * @param  array|string  $key
     * @return bool
     */
    public function anyBagHasAny($key)
    {
        $keys = is_array($key) ? $key : func_get_args();
        $messageKeys = [];

        foreach ($this->bags as $bag) {
            $messageKeys = array_merge($messageKeys, $bag->keys());
        }

        return ! empty(array_intersect($keys, $messageKeys));
    }

    /**
     * Dynamically call methods on the default bag.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->getBag('default')->$method(...$parameters);
    }

    /**
     * Dynamically access a view error bag.
     *
     * @param  string  $key
     * @return \Illuminate\Contracts\Support\MessageBag
     */
    public function __get($key)
    {
        return $this->getBag($key);
    }

    /**
     * Dynamically set a view error bag.
     *
     * @param  string  $key
     * @param  \Illuminate\Contracts\Support\MessageBag  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->put($key, $value);
    }
}
