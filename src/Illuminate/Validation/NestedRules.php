<?php

namespace Illuminate\Validation;

class NestedRules
{
    /**
     * The callback to execute.
     *
     * @var callable
     */
    protected $callback;

    /**
     * Create a new nested rule instance.
     *
     * @param  callable  $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Compile the callback into an array of rules.
     *
     * @param  mixed  $args
     * @return array
     */
    public function compile(...$args)
    {
        return call_user_func($this->callback, ...$args);
    }
}
