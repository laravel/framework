<?php

namespace Illuminate\Validation\Rules;

class RequiredIf
{
    /**
     * The condition that validates the attribute.
     *
     * @var callable|bool
     */
    public $condition;

    /**
     * Create a new required validation rule based on a condition.
     *
     * @param  callable|bool  $condition
     * @return void
     */
    public function __construct($condition)
    {
        $this->condition = $condition;
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        if (is_callable($this->condition)) {
            return call_user_func($this->condition) ? 'required' : '';
        }

        return $this->condition ? 'required' : '';
    }
}
