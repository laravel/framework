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
     * Next invocation of __call returns HigherOrderTapProxy.
     *
     * @var bool
     */
    protected $continueChain = false;

    /**
     * Create a new tap proxy instance.
     *
     * @param  mixed  $target
     * @return void
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
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $this->target->{$method}(...$parameters);

        if ($this->continueChain) {
            return $this;
        }

        return $this->target;
    }

    /**
     * Allow continuous chaining of method calls on tap target.
     *
     * @return Illuminate\Support\HigherOrderTapProxy
     */
    public function chain()
    {
        $this->continueChain = true;

        return $this;
    }

    /**
     * Stop chaining and return the tap target.
     *
     * @return mixed
     */
    public function break()
    {
        return $this->target;
    }
}
