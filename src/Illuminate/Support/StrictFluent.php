<?php

namespace Illuminate\Support;

use Closure;
use InvalidArgumentException;

class StrictFluent extends Fluent
{

    /**
     * Instantiate StrictFluent with an array full of nulls.
     *
     * @param  array  $keys
     * @return static
     */
    public static function withNullArray($keys)
    {
        return new static(array_fill_keys($keys, null));
    }

    /**
     * Execute a Closure using this object as its first parameter.
     *
     * @param  Closure  $closure
     * @return $this
     */
    public function applyClosure(Closure $closure)
    {
        call_user_func($closure, $this);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function __set($key, $value)
    {
        $this->validateKey($key);

        parent::__set($key, $value);
    }

    /**
     * @inheritdoc
     */
    public function __call($method, $parameters)
    {
        $this->validateKey($method);

        return parent::__call($method, $parameters);
    }

    /**
     * Ensure that the key already exists.
     *
     * @param  string  $key
     */
    protected function validateKey($key)
    {
        if (! array_key_exists($key, $this->attributes)) {
            throw new InvalidArgumentException("Key [$key] is not allowed.");
        }
    }
}
