<?php

namespace Illuminate\Support;

class HigherOrderWhenProxy
{
    /**
     * The resource being operated on.
     *
     * @var mixed
     */
    protected $resource;

    /**
     * The condition for the query.
     *
     * @var mixed
     */
    protected $condition;

    /**
     * Create a new proxy instance.
     *
     * @param  mixed  $resource
     * @param  mixed  $condition
     */
    public function __construct($resource, $condition)
    {
        $this->resource = $resource;
        $this->condition = $condition;
    }

    /**
     * Proxy the call onto the resource.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($this->condition) {
            return $this->resource->{$method}(...$parameters);
        }

        return $this->resource;
    }
}
