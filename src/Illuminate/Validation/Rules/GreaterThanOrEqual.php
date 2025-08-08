<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class GreaterThanOrEqual implements Stringable
{
    public function __construct(protected string|int $value)
    {
    }

    public function __toString(): string
    {
        return "gte:{$this->value}";
    }
}
