<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;

class Sometimes implements Rule
{
    /**
     * Fields to apply validation.
     *
     * @var array|string
     */
    protected $rules;

    /**
     * Callbacks to determine rule.
     *
     * @var array
     */
    protected $callbacks = [];

    /**
     * Create a new sometimes rule instance.
     *
     * @param  array|callable  $callbacks
     * @param  string|array  $rules
     * @return void
     */
    public function __construct($rules, $callbacks)
    {
        $this->rules = $rules;

        if (is_callable($callbacks)) {
            $callbacks = [$callbacks];
        }

        $this->callbacks = $callbacks;
    }

    /**
     * @param  callable  $callback
     */
    public function when(callable $callback)
    {
        $this->callbacks[] = $callback;
    }

    /**
     * Apply rule to validator.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @param  string  $field
     */
    public function apply(Validator $validator, $field)
    {
        $validator->sometimes($field, $this->rules, function () {
            foreach ($this->callbacks as $callback) {
                if (! call_user_func($callback, func_get_args())) {
                    return false;
                }
            }

            return true;
        });
    }
}
