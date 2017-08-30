<?php

namespace Illuminate\Support;

class Optional
{
    use Traits\Macroable;

    /**
     * The underlying object.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Create a new optional instance.
     *
     * @param  mixed  $value
     * @return void
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Check if Optional has a value.
     *
     * @return bool
     */
    public function hasValue()
    {
        return is_object($this->value);
    }

    /**
     * Check if Optional is empty.
     *
     * @return bool
     */
    public function empty()
    {
        return ! $this->hasValue();
    }

    /**
     * Get underlying value.
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Dynamically access a property on the underlying object.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        if ($this->hasValue()) {
            return new self($this->value->{$key});
        }

        return $this;
    }

    /**
     * Dynamically set a property on the underlying object.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return mixed
     */
    public function __set($key, $value)
    {
        if ($this->hasValue()) {
            $this->value->{$key} = $value;
        }
    }

    /**
     * Dynamically pass a method to the underlying object.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($this->hasValue()) {
            return new self($this->value->{$method}(...$parameters));
        }

        return $this;
    }
}
