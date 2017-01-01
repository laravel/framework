<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule as ValidationRule;

abstract class Rule
{
    /**
     * The string representation of the rule.
     *
     * @return string
     */
    abstract public function toString();

    /**
     * Add the rule to the stack and continue.
     *
     * @return \Illuminate\Validation\Rule
     */
    public function also()
    {
        ValidationRule::$output .= (string) $this.'|';

        return new ValidationRule;
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        $stack = ValidationRule::$output;

        ValidationRule::$output = null;

        return $stack ? $stack.$this->toString() : $this->toString();
    }
}
