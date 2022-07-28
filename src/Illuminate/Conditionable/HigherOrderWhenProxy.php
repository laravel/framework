<?php

namespace Illuminate\Support;

class HigherOrderWhenProxy
{
    /**
     * The target being conditionally operated on.
     *
     * @var mixed
     */
    protected $target;

    /**
     * The condition for proxying.
     *
     * @var bool
     */
    protected $condition;

    /**
     * Tracks whether the proxy has a condition.
     *
     * @var bool
     */
    protected $hasCondition = false;

    /**
     * Determine whether the condition should be negated.
     *
     * @var bool
     */
    protected $negateConditionOnCapture;

    /**
     * Create a new proxy instance.
     *
     * @param  mixed  $target
     * @return void
     */
    public function __construct($target)
    {
        $this->target = $target;
    }

    /**
     * Set the condition on the proxy.
     *
     * @param  bool  $condition
     * @return $this
     */
    public function condition($condition)
    {
        $this->condition = $condition;
        $this->hasCondition = true;

        return $this;
    }

    /**
     * Negate the condition.
     *
     * @return $this
     */
    public function negateConditionOnCapture()
    {
        $this->negateConditionOnCapture = true;

        return $this;
    }

    /**
     * Proxy accessing an attribute onto the target.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->condition
            ? $this->target->{$key}
            : $this->target;
    }

    /**
     * Proxy a method call on the target.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (! $this->hasCondition) {
            $condition = $this->target->{$method}(...$parameters);

            return $this->condition($this->negateConditionOnCapture ? ! $condition : $condition);
        }

        return $this->condition
            ? $this->target->{$method}(...$parameters)
            : $this->target;
    }
}
