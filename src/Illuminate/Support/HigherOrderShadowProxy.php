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
     * If the object should be returned instead.
     *
     * @var bool
     */
    protected $tap;

    /**
     * Create a new shadow proxy instance.
     *
     * @param  mixed  $target
     * @param  bool  $tap
     * @return void
     */
    public function __construct($target, $tap)
    {
        $this->target = $target;
        $this->tap = $tap;
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
        $result = false;

        if (method_exists($this->target, $method) ||
            method_exists($this->target, 'hasMacro') && $this->target::hasMacro($method)) {
            $result = $this->target->$method(...$parameters);
        }

        return $this->tap ? $this->target : $result;
    }
}
