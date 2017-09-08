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
    }

    /**
     * Create a new instance with a nullable value.
     * @param mixed $value
     * @return self
     */
    public static function of($value)
    {
        return new self($value);
    }

    /**
     * Create a new instance with some value.
     * @param mixed $value
     * @return self
     */
    public static function some($value)
    {
        if (is_null($value)) {
            throw new \InvalidArgumentException('Null value passed to some() method. Use none() instead.');
        }

        return new self($value);
    }

    /**
     * Create a new instance with no value.
     * @return self
     */
    public static function none()
    {
        return new self(null);
    }
}
