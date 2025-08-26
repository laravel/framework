<?php

namespace Illuminate\Support;

use ArrayAccess;
use ArrayObject;
use Illuminate\Support\Traits\Macroable;

class Optional implements ArrayAccess
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The underlying object.
     */
    protected $value;

    /**
     * Create a new optional instance.
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Dynamically access a property on the underlying object.
     *
     * @param  string  $key
     */
    public function __get($key)
    {
        if (is_object($this->value)) {
            return $this->value->{$key} ?? null;
        }
    }

    /**
     * Dynamically check a property exists on the underlying object.
     *
     * @return bool
     */
    public function __isset($name)
    {
        if (is_object($this->value)) {
            return isset($this->value->{$name});
        }

        if (is_array($this->value) || $this->value instanceof ArrayObject) {
            return isset($this->value[$name]);
        }

        return false;
    }

    /**
     * Determine if an item exists at an offset.
     */
    public function offsetExists($key): bool
    {
        return Arr::accessible($this->value) && Arr::exists($this->value, $key);
    }

    /**
     * Get an item at a given offset.
     */
    public function offsetGet($key): mixed
    {
        return Arr::get($this->value, $key);
    }

    /**
     * Set the item at a given offset.
     */
    public function offsetSet($key, $value): void
    {
        if (Arr::accessible($this->value)) {
            $this->value[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     */
    public function offsetUnset($key): void
    {
        if (Arr::accessible($this->value)) {
            unset($this->value[$key]);
        }
    }

    /**
     * Dynamically pass a method to the underlying object.
     *
     * @param  string  $method
     * @param  array  $parameters
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (is_object($this->value)) {
            return $this->value->{$method}(...$parameters);
        }
    }
}
