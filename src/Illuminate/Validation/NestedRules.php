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
     * @param  string  $attr
     * @param  mixed  $value
     * @param  array  $data
     *
     * @return array
     */
    public function compile($attribute, $value, $data)
    {
        return call_user_func($this->callback, $attribute, $value, $data);
    }
}
