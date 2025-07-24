<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class NotRegex implements Stringable
{
    public function __construct(protected string $pattern) {}

    public function __toString(): string
    {
        return "not_regex:{$this->pattern}";
    }
}
