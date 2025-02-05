<?php

namespace Illuminate\Support;

class HigherOrderTapProxy
{
    /**
     * Create a new tap proxy instance.
     *
     * @param  mixed  $target
     * @return void
     */
    public function __construct(
        public mixed $target
    )
    {}

    /**
     * Dynamically pass method calls to the target.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $this->target->{$method}(...$parameters);

        return $this->target;
    }
}
