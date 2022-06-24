<?php

namespace Illuminate\Support;

use ArrayAccess;
use ArrayIterator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use IteratorAggregate;
use JsonSerializable;
use Stringable;

class Json implements ArrayAccess, Stringable, JsonSerializable, IteratorAggregate, Arrayable, Jsonable
{
    use Traits\Conditionable;
    use Traits\Tappable;

    /**
     * The JSON array.
     *
     * @var array
     */
    protected array $json = [];

    /**
     * Create a new Json instance.
     *
     * @param  array  $json
     */
    public function __construct(array $json = [])
    {
        $this->json = $json;
    }

    /**
     * Returns the underlying JSON array.
     *
     * @return array
     */
    public function all()
    {
        return $this->json;
    }

    /**
     * Get an item from JSON using "dot" notation.
     *
     * @param  string|int  $key
     * @param  mixed|null  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return Arr::get($this->json, $key, $default);
    }

    /**
     * Check if an item or items exist in JSON using "dot" notation.
     *
     * @param  string|int  ...$keys
     * @return bool
     */
    public function has(...$keys)
    {
        return Arr::has($this->json, $keys);
    }

    /**
     * Determine if any of the keys exist in JSON using "dot" notation.
     *
     * @param  string|int  ...$keys
     * @return bool
     */
    public function hasAny(...$keys)
    {
        return Arr::hasAny($this->json, $keys);
    }

    /**
     * Check if the given key is missing using dot notation.
     *
     * @param  string|int  $key
     * @return bool
     */
    public function missing($key)
    {
        return ! $this->has($key);
    }

    /**
     * Set a JSON item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire JSON will be replaced.
     *
     * @param  string|int|null  $key
     * @param  mixed  $value
     * @return $this
     */
    public function set($key, $value)
    {
        Arr::set($this->json, $key, $value);

        return $this;
    }

    /**
     * Remove one or many items from JSON using "dot" notation.
     *
     * @param  string|int  ...$keys
     * @return $this
     */
    public function forget(...$keys)
    {
        Arr::forget($this->json, $keys);

        return $this;
    }

    /**
     * Returns a Json instance as a Collection.
     *
     * @param  string|null  $key
     * @return \Illuminate\Support\Collection
     */
    public function collect($key = null)
    {
        return new Collection($this->get($key));
    }

    /**
     * Dynamically get JSON values.
     *
     * @param  string  $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Dynamically set JSON values.
     *
     * @param  string  $name
     * @param  mixed  $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * Dynamically check a JSON key presence.
     *
     * @param  string  $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * Dynamically unset a JSON key.
     *
     * @param  string  $name
     * @return void
     */
    public function __unset($name)
    {
        $this->forget($name);
    }

    /**
     * Whether an offset exists.
     *
     * @param  mixed
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Offset to retrieve.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Offset to set.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Offset to unset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        $this->forget($offset);
    }

    /**
     * Get a string representation of the object.
     *
     * @return string
     */
    public function toString()
    {
        return $this->toJson();
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Retrieve an external iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(fn ($item) => $item instanceof Arrayable ? $item->toArray() : $item, $this->json);
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return false|string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Create a new Json instance.
     *
     * @param  array  $json
     * @return static
     */
    public static function make($json)
    {
        return new static($json);
    }

    /**
     * Create a new Json instance from a JSON string.
     *
     * @param  string  $json
     * @param  int  $depth
     * @param  int  $options
     * @return static
     */
    public static function fromJson($json, $depth = 512, $options = 0)
    {
        return new static(json_decode($json, true, $depth, $options));
    }

    /**
     * Wraps an array into a Json instance.
     *
     * @param  self|array|null  $json
     * @return static
     */
    public static function wrap($json)
    {
        return $json instanceof static ? $json : new static((array) $json);
    }
}

