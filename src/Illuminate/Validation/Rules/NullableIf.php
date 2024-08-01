<?php

namespace Illuminate\Validation\Rules;

use InvalidArgumentException;
use Stringable;

class NullableIf implements Stringable
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
        if (! is_callable($condition) && ! is_bool($condition)) {
            throw new InvalidArgumentException('The provided condition must be a callable or boolean.');
        }

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
            return call_user_func($this->condition) ? 'nullable' : '';
        }

        return $this->condition ? 'nullable' : '';
    }
}
