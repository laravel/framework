<?php

namespace Illuminate\Validation\Rules;

use Closure;
use InvalidArgumentException;
use Stringable;

class ExcludeIf implements Stringable
{
    /**
     * The condition that validates the attribute.
     *
     * @var \Closure|bool
     */
    public $condition;

    /**
     * Create a new exclude validation rule based on a condition.
     *
     * @param  \Closure|bool  $condition
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($condition)
    {
        if (! $condition instanceof Closure && ! is_bool($condition)) {
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
            return call_user_func($this->condition) ? 'exclude' : '';
        }

        return $this->condition ? 'exclude' : '';
    }
}
