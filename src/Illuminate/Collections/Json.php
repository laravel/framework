<?php

namespace Illuminate\Support;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Tappable;
use JsonSerializable;
use Stringable;

class Json implements ArrayAccess, Arrayable, JsonSerializable, Jsonable, Stringable
{
    use Conditionable;
    use Tappable;

    /**
     * The underlying JSON data.
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new Json instance.
     *
     * @param  iterable  $data
     */
    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    /**
     * Returns a JSON value in "dot" notation.
     *
     * @param  array|string|int|null  $key
     * @param  mixed|null  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * Sets a JSON value in "dot" notation.
     *
     * @param  array|string|int  $key
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
     * Determine a given key in "dot" notation exists in the JSON data.
     *
     * @param  string|int  $key
     * @return bool
     */
    public function has($key)
    {
        return $this->get($key) !== null;
    }

    /**
     * Removes a JSON key.
     *
     * @param  string|int  $key
     * @return $this
     */
    public function forget($key)
    {
        $segment = &$this->data;

        $keys = explode('.', $key) ?: [$key];

        foreach ($keys as $index => $name) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$index]);

            if (is_array($segment) && array_key_exists($name, $segment)) {
                $segment = &$segment[$name];
            } elseif (is_object($segment) && property_exists($segment, $name)) {
                $segment = &$segment->{$name};
            }
        }

        if (is_array($segment)) {
            unset($segment[array_shift($keys)]);
        } elseif(is_object($segment)) {
            unset($segment->{array_shift($keys)});
        }

        return $this;
    }

    /**
     * Dynamically get JSON values.
     *
     * @param  string  $name
     * @return mixed
     */
    public function __get(string $name): mixed
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
    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    /**
     * Dynamically check a JSON key is set.
     *
     * @param  string  $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    /**
     * Dynamically unset a JSON key.
     *
     * @param  string  $name
     * @return void
     */
    public function __unset(string $name): void
    {
        $this->forget($name);
    }

    /**
     * Whether an offset exists.
     *
     * @param  mixed
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Offset to retrieve.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
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
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Offset to unset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->forget($offset);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the Json instance to JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Returns string representation of the object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Create a new Json instance.
     *
     * @param  string|iterable  $json
     * @return static
     */
    public static function make($json = null)
    {
        if (is_string($json)) {
            $json = json_decode($json, true);
        }

        return new static((array) $json);
    }
}
