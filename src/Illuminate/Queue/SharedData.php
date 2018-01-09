<?php

namespace Illuminate\Queue;

use Serializable;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Support\Arrayable;

class SharedData implements Arrayable, Serializable
{
    use SerializesAndRestoresModelIdentifiers;

    /**
     * The items contained in the shared data.
     *
     * @var array
     */
    protected $items = [];

    /**
     * SharedData constructor.
     *
     * @param  array  $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Prepare the instance for serialization.
     *
     * @return array
     */
    public function serialize()
    {
        return serialize($this->serializing($this->items));
    }

    /**
     * Restore the model after serialization.
     *
     * @return void
     */
    public function unserialize($serialized)
    {
        $this->items = $this->unserializing(unserialize($serialized));
    }

    /**
     * Determine if the shared data is empty or not.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * Determine if the shared data is not empty.
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return ! $this->isEmpty();
    }

    /**
     * Get an item from the shared data by key.
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return Arr::get($this->items, $key, $default);
    }

    /**
     * Put an item in the shared data by key.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return $this
     */
    public function put($key, $value)
    {
        $this->items[$key] = $value;

        return $this;
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($value) {
            return $value instanceof Arrayable ? $value->toArray() : $value;
        }, $this->items);
    }

    /**
     * Recursively serialize the values inside the shared data.
     *
     * @param  array  $items
     * @return array
     */
    protected function serializing(array $items)
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return $this->serializing($value);
            }

            return $this->getSerializedPropertyValue($value);
        }, $items);
    }

    /**
     * Recursively unserialize the values inside the shared data.
     *
     * @param  array  $items
     * @return array
     */
    protected function unserializing(array $items)
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return $this->unserializing($value);
            }

            return $this->getRestoredPropertyValue($value);
        }, $items);
    }
}
