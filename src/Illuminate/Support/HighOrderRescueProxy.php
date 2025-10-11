<?php

namespace Illuminate\Support;

use Throwable;

class HighOrderRescueProxy
{
    /**
     * The target being tried.
     *
     * @var mixed
     */
    public $target;

    /**
     * The catch callback for the exception.
     *
     * @var (callable(\Throwable):void)|null
     */
    public $callback;

    /**
     * Create a new try proxy instance.
     *
     * @param  mixed  $target
     * @param  (callable(\Throwable):void)|null  $callback
     */
    public function __construct($target, $callback)
    {
        $this->target = $target;
        $this->callback = $callback;
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
        try {
            $this->target->{$method}(...$parameters);
        } catch (Throwable $exception) {

        }

        return $this->target;
    }
}
