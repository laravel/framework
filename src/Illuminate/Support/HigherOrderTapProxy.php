<?php

namespace Illuminate\Support;

/**
 * @template Target
 */
class HigherOrderTapProxy
{
    /**
     * Create a new tap proxy instance.
     *
     * @param  Target  $target  The target being tapped.
     */
    public function __construct(public $target)
    {
        $this->target = $target;
    }

    /**
     * Dynamically pass method calls to the target.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return Target
     */
    public function __call($method, $parameters)
    {
        $this->target->{$method}(...$parameters);

        return $this->target;
    }
}
