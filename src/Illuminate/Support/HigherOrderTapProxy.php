<?php

namespace Illuminate\Support;

/**
 * @template Target
 */
class HigherOrderTapProxy
{
    /**
     * The target being tapped.
     *
     * @var Target
     */
    public $target;

    /**
     * Create a new tap proxy instance.
     *
     * @param  Target  $target
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
     * @return Target
     */
    public function __call($method, $parameters)
    {
        $this->target->{$method}(...$parameters);

        return $this->target;
    }
}
