<?php

namespace Illuminate\Database;

class HigherOrderWhenProxy
{
    /**
     * The builder being operated on.
     *
     * @var mixed
     */
    protected $builder;

    /**
     * The condition for the query.
     *
     * @var mixed
     */
    protected $condition;

    /**
     * Create a new proxy instance.
     *
     * @param  mixed  $builder
     * @param  mixed  $condition
     */
    public function __construct($builder, $condition)
    {
        $this->builder = $builder;
        $this->condition = $condition;
    }

    /**
     * Proxy the call onto the query builder.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($this->condition) {
            return $this->builder->{$method}(...$parameters);
        }

        return $this->builder;
    }
}
