<?php

namespace Illuminate\Validation\Rules;

use Closure;
use InvalidArgumentException;
use Stringable;

class Min implements Stringable
{
    /**
     * The value that validates the attribute.
     *
     * @var callable|int
     */
    public $value;

    /**
     * Create a new min validation rule based on a value.
     *
     * @param  callable|int  $value
     */
    public function __construct($value)
    {
        if ($value instanceof Closure || is_int($value)) {
            $this->value = $value;
        } else {
            throw new InvalidArgumentException('The provided value must be a callable or an integer.');
        }
    }

    public function __toString(): string
    {
        if (is_callable($this->value)) {
            $this->value = call_user_func($this->value);
        }

        return "min:{$this->value}";
    }
}
