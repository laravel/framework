<?php

namespace Illuminate\Support;

class HigherOrderTapProxy
{
    /**
     * The target being tapped.
     *
     * @var mixed
     */
    public $target;

    /**
     * If the tap proxy is chainable.
     *
     * @var bool
     */
    public $chainable;

    /**
     * Create a new tap proxy instance.
     *
     * @param  mixed  $target
     * @param  bool  $chainable
     * @return void
     */
    public function __construct($target, $chainable = false)
    {
        $this->target = $target;
        $this->chainable = $chainable;
    }

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

        return $this->chainable ? $this : $this->target;
    }

    /**
     * Create a new chainable tap proxy.
     *
     * @return static
     */
    public function chain()
    {
        return new static($this->target, true);
    }

    /**
     * Call the given Closure with the given value then return the value.
     *
     * @param  callable|null  $callback
     * @return mixed
     */
    public function then($callback = null)
    {
        if (is_null($callback)) {
            return $this->target;
        }

        return with($this->target, $callback);
    }
}
