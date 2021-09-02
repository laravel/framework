<?php

namespace Illuminate\Support;

use ArrayIterator;
use Illuminate\Contracts\Support\ValidatedData;
use stdClass;

class ValidatedInput implements ValidatedData
{
    /**
     * The underlying input.
     *
     * @var array
     */
    protected $input;

    /**
     * Create a new validated input container.
     *
     * @param  array  $input
     * @return void
     */
    public function __construct(array $input)
    {
        $this->input = $input;
    }

    /**
     * Get a subset containing the provided keys with values from the input data.
     *
     * @param  array|mixed  $keys
     * @return array
     */
    public function only($keys)
    {
        $results = [];

        $input = $this->input;

        $placeholder = new stdClass;

        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            $value = data_get($input, $key, $placeholder);

            if ($value !== $placeholder) {
                Arr::set($results, $key, $value);
            }
        }

        return $results;
    }

    /**
     * Get all of the input except for a specified array of items.
     *
     * @param  array|mixed  $keys
     * @return array
     */
    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $results = $this->input;

        Arr::forget($results, $keys);

        return $results;
    }

    /**
     * Merge the validated input with the given array of additional data.
     *
     * @param  array  $items
     * @return static
     */
    public function merge(array $items)
    {
        return new static(array_merge($this->input, $items));
    }

    /**
     * Get the input as a collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collect()
    {
        return new Collection($this->input);
    }

    /**
     * Get the raw, underlying input array.
     *
     * @return array
     */
    public function all()
    {
        return $this->input;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->all();
    }

    /**
     * Dynamically access input data.
     *
     * @param  string  $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->input[$name];
    }

    /**
     * Dynamically set input data.
     *
     * @param  string  $name
     * @param  mixed  $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        $this->input[$name] = $value;
    }

    /**
     * Determine if an input key is set.
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->input[$name]);
    }

    /**
     * Remove an input key.
     *
     * @param  string  $name
     * @return void
     */
    public function __unset($name)
    {
        unset($this->input[$name]);
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($key)
    {
        return isset($this->input[$key]);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        return $this->input[$key];
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->input[] = $value;
        } else {
            $this->input[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($key)
    {
        unset($this->input[$key]);
    }

    /**
     * Get an iterator for the input.
     *
     * @return \ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->input);
    }
}
