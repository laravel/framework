<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class NotRegex implements Stringable
{
    /**
     * Create a new not_regex rule instance.
     */
    public function __construct(protected string $pattern)
    {
    }

    /**
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        return "not_regex:{$this->pattern}";
    }
}
