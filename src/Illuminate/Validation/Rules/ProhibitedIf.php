<?php

namespace Illuminate\Validation\Rules;

use InvalidArgumentException;

class ProhibitedIf
{
    /**
     * The condition that validates the attribute.
     *
     * @var callable|bool
     */
    public $condition;

    /**
     * Create a new prohibited validation rule based on a condition.
     *
     * @param  callable|bool  $condition
     * @return void
     */
    public function __construct($condition)
    {
        if (! is_string($condition)) {
            $this->condition = $condition;
        } else {
            throw new InvalidArgumentException('The provided condition must be a callable or boolean.');
        }
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        if (is_callable($this->condition)) {
            return call_user_func($this->condition) ? 'prohibited' : '';
        }

        return $this->condition ? 'prohibited' : '';
    }
}
