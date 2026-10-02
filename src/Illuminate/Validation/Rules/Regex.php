<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Regex implements Stringable
{
    public function __construct(protected string $pattern)
    {
    }

    public function __toString(): string
    {
        return "regex:{$this->pattern}";
    }
}
