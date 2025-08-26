<?php

namespace Illuminate\Support;

class HigherOrderTapProxy
{
    /**
     * The target being tapped.
     */
    public $target;

    /**
     * Create a new tap proxy instance.
     */
    public function __construct($target)
    {
        $this->target = $target;
    }

    /**
     * Dynamically pass method calls to the target.
     *
     * @param  string  $method
     * @param  array  $parameters
     */
    public function __call($method, $parameters)
    {
        $this->target->{$method}(...$parameters);

        return $this->target;
    }
}
