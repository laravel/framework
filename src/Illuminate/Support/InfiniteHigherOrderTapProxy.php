<?php

namespace Illuminate\Support;

class InfiniteHigherOrderTapProxy extends HigherOrderTapProxy
{
    /**
     * Stop chaining and return the original object.
     *
     * @return mixed
     */
    public function finally()
    {
        return $this->target;
    }

    /**
     * Dynamically pass method calls to the target.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return Illuminate\Support\InfiniteHigherOrderTapProxy
     */
    public function __call($method, $parameters)
    {
        parent::__call($method, $parameters);

        return $this;
    }
}
