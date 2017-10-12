<?php

namespace Illuminate\Support;

class Optional
{
    use Traits\Macroable {
        __call as macroCall;
    }

    /**
     * The underlying object.
     *
     * @var mixed
     */
    protected $value;

    /**
     * The default value returned if the property is unavailable on the underlying object.
     *
     * @var mixed
     */
    protected $default;

    /**
     * Create a new optional instance.
     *
     * @param  mixed  $value
     * @param  mixed  $default
     * @return void
     */
    public function __construct($value, $default = null)
    {
        $this->value = $value;
        $this->default = $default;
    }

    /**
     * Dynamically access a property on the underlying object.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        if (is_object($this->value)) {
            return $this->value->{$key};
        }

        return $this->default;
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
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (is_object($this->value)) {
            return $this->value->{$method}(...$parameters);
        }

        return $this->default;
    }
}
