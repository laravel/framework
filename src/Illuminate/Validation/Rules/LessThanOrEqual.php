<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class LessThanOrEqual implements Stringable
{
    public function __construct(protected string|int $value)
    {
    }

    public function __toString(): string
    {
        return "lte:{$this->value}";
    }
}
