<?php

namespace Illuminate\Database\Eloquent\Factories;

use ArrayAccess;
use Illuminate\Database\Eloquent\Model;

class Definition implements ArrayAccess
{
    /**
     * Attributes to fill the model
     *
     * @var array<string, mixed>
     */
    protected $attributes = [];

    /**
     * Create a new definition instance.
     *
     * @param  iterable<string, mixed>  $attributes
     * @return void
     */
    public function __construct($attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * Get the raw definition attributes from this instance.
     *
     * @return array<string, mixed>
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Expands an attribute value to its final value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function expand($value)
    {
        return match (true) {
            $value instanceof Factory => $value->create()->getKey(),
            $value instanceof Model => $value->getKey(),
            is_callable($value) && !is_string($value) && !is_array($value) => $value($this),
            default => $value,
        };
    }

    /**
     * Get an attribute final value from this instance.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key] = $this->expand($this->attributes[$key]);
        }

        return value($default, $this);
    }

    /**
     * Return all expanded attributes of this definition instance as an array.
     *
     * @return array<string, mixed>
     */
    public function all()
    {
        foreach ($this->attributes as $key => $value) {
            $this->attributes[$key] = $this->expand($value);
        }

        return $this->attributes;
    }

    /**
     * Merges attributes on top of the current definition attributes.
     *
     * @param  static|array<string, mixed>  $attributes
     * @return $this
     */
    public function merge($attributes)
    {
        $this->attributes = array_merge(
            $this->attributes, $attributes instanceof static ? $attributes->getAttributes() : $attributes
        );

        return $this;
    }

    /**
     * Dynamically retrieve the value of an attribute.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Dynamically set the value of an attribute.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Dynamically check if an attribute is set.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }


    /**
     * Dynamically unset an attribute.
     *
     * @param  mixed  $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Set the value at the given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->attributes[$offset] = $value;
    }

    /**
     * Unset the value at the given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Wraps an iterable of attributes into a Definition instance.
     *
     * @param  static|iterable<string, mixed>  $attributes
     * @return static
     */
    public static function wrap($attributes = [])
    {
        return $attributes instanceof static ? $attributes : new static($attributes);
    }
}
