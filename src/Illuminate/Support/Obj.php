<?php

namespace Illuminate\Support;

use ArrayAccess;
use InvalidArgumentException;

class Obj implements ArrayAccess
{
    use Traits\Macroable {
        __call as macroCall;
    }

    /**
     * The underlying array value.
     *
     * @var array
     */
    protected $value;

    /**
     * Construct a new object helper instance.
     *
     * @param array|object $value
     */
    public function __construct($value)
    {
        $this->value = $this->ensureArray($value);
    }

    /**
     * Ensure the given value is an array,
     * or throw an exception if not possible.
     *
     * @param $value
     */
    protected function ensureArray($value)
    {
        if (is_array($value) && Arr::isAssoc($value)) {
            return $value;
        }

        if (is_object($value) && $value instanceof ArrayAccess) {
            return (array) $value;
        }

        throw new InvalidArgumentException('The value provided is not an array or an object that implements ArrayAccess');
    }

    /**
     * Get the underlying value as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->value;
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return Arr::accessible($this->value) && Arr::exists($this->value, $key);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return Arr::get($this->value, $key);
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (Arr::accessible($this->value)) {
            $this->value[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        if (Arr::accessible($this->value)) {
            unset($this->value[$key]);
        }
    }

    /**
     * Dynamically access the given property on the underlying value.
     *
     * @param string $name
     * @return \Illuminate\Support\Obj|mixed
     */
    public function __get(string $name)
    {
        $value = $this->value[$name];

        return is_array($value) && Arr::isAssoc($value)
            ? new static($value) : $value;
    }

    /**
     * Dynamically pass a method to the underlying value.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->value[$method](...$parameters);
    }
}
