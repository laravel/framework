<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class LessThan implements Stringable
{
    public function __construct(protected string|int $value)
    {
    }

    public function __toString(): string
    {
        return "lt:{$this->value}";
    }
}
