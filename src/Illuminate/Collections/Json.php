<?php

namespace Illuminate\Support;

use ArrayAccess;
use ArrayIterator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use IteratorAggregate;
use JsonSerializable;
use Stringable;
use Traversable;

class Json implements Stringable, ArrayAccess, JsonSerializable, IteratorAggregate, Arrayable, Jsonable
{
    use Traits\Conditionable;
    use Traits\Tappable;

    /**
     * The underlying JSON data.
     *
     * @var mixed
     */
    protected $data;

    /**
     * Create a new JSON instance
     *
     * @param  mixed  $data
     */
    public function __construct($data = null)
    {
        $this->data = $data;
    }

    /**
     * Returns the underlying JSON object or array.
     *
     * @return mixed
     */
    public function data()
    {
        return $this->get(null);
    }

    /**
     * Returns a JSON value from a key in "dot" notation.
     *
     * @param  string|int|null  $key
     * @param  mixed|null  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * Sets a value into the JSON data using a key in "dot" notation.
     *
     * @param  string|int  $key
     * @param  mixed  $value
     * @param  bool  $overwrite
     * @return $this
     */
    public function set($key, $value, $overwrite = true)
    {
        data_set($this->data, $key, $value, $overwrite);

        return $this;
    }

    /**
     * Sets a value into the JSON data using a key in "dot" notation if the key is missing.
     *
     * @param  string|int  $key
     * @param  mixed  $value
     * @return $this
     */
    public function fill($key, $value)
    {
        return $this->set($key, $value, false);
    }

    /**
     * Check if a given key is set and not null using dot notation.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return $this->get($key) !== null;
    }

    /**
     * Check if the given key is missing using dot notation.
     *
     * @param  string  $key
     * @return bool
     */
    public function missing($key)
    {
        return !$this->has($key);
    }

    /**
     * Removes a JSON key.
     *
     * @param  string|int  $key
     * @return void
     */
    public function unset($key)
    {
        $segment = $this->data;

        $keys = explode('.', $key) ?: [$key];

        foreach ($keys as $index => $name) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$index]);

            if (is_array($segment) && array_key_exists($name, $segment)) {
                $segment = &$segment[$name];
            } elseif (property_exists($segment, $name)) {
                $segment = &$segment->{$name};
            }
        }

        if (is_array($segment)) {
            unset($segment[array_shift($keys)]);
        } else {
            unset($segment->{array_shift($keys)});
        }
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
    public function __get($name): mixed
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
    public function __set($name, $value): void
    {
        $this->set($name, $value);
    }

    /**
     * Dynamically check a JSON key presence.
     *
     * @param  string  $name
     * @return bool
     */
    public function __isset($name): bool
    {
        return $this->has($name);
    }

    /**
     * Dynamically unset a JSON key.
     *
     * @param  string  $name
     * @return void
     */
    public function __unset($name): void
    {
        $this->unset($name);
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
        $this->unset($offset);
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string.
     */
    public function __toString(): string
    {
        return $this->toJson();
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
    public function getIterator(): Traversable
    {
        return match (true) {
            $this->data instanceof IteratorAggregate => $this->data->getIterator(),
            is_array($this->data) => new ArrayIterator($this->data),
            default => new ArrayIterator(get_object_vars($this->data)),
        };
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($value) {
            return $value instanceof Arrayable ? $value->toArray() : $value;
        }, match (true) {
            $this->data instanceof Traversable, => iterator_to_array($this->data),
            is_object($this->data) => get_object_vars($this->data),
            default => $this->data,
        });
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
     * @param  array|object|null  $json
     * @return $this
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
    public static function fromString($json, $depth = 512, $options = 0)
    {
        return new static(json_decode($json, false, $depth, $options));
    }

    /**
     * Wraps an array into a Json instance.
     *
     * @param  object|array|null  $json
     * @return static
     */
    public static function wrap($json)
    {
        return $json instanceof static ? $json : new static($json);
    }
}
