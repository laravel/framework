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
     * @param  (Closure(): bool)|null  $until
     */
    public function __construct(public $target, public ?Closure $until = null)
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

        return $this->until !== null && $until()
            ? $this
            : $this->target;
    }
}
