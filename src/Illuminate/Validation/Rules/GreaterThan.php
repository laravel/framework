<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class GreaterThan implements Stringable
{
    public function __construct(protected string|int $value) {}

    public function __toString(): string
    {
        return "gt:{$this->value}";
    }
}
