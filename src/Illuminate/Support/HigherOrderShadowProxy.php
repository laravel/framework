<?php

namespace Illuminate\Support;

class HigherOrderShadowProxy
{
    /**
     * The target being tapped.
     *
     * @var object
     */
    public $target;

    /**
     * Create a new shadow proxy instance.
     *
     * @param  mixed  $target
     * @return void
     */
    public function __construct($target)
    {
        $this->target = $target;
    }

    /**
     * Dynamically pass a method call to the target if it exists.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->target, $method) ||
            method_exists($this->target, 'hasMacro') && $this->target::hasMacro($method)) {
            return $this->target->$method(...$parameters);
        }

        return false;
    }
}
