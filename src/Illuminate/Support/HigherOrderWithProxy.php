<?php

namespace Illuminate\Support;

class HigherOrderWithProxy
{
    /**
     * The target being proxied.
     *
     * @var mixed
     */
    public $target;

    /**
     * Create a new "with" proxy instance.
     *
     * @param  mixed  $target
     * @return void
     */
    public function __construct($target)
    {
        $this->target = $target;
    }

    /**
     * Proxy accessing a property on the target object.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        if (! is_null($this->target)) {
            return $this->target->{$key};
        }
    }

    /**
     * Proxy a method call onto the collection items.
     *
     * @param  string  $method
     * @param  array  $params
     * @return mixed
     */
    public function __call($method, $params)
    {
        if (! is_null($this->target)) {
            return $this->target->{$method}(...$params);
        }
    }
}
