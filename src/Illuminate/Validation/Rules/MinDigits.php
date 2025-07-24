<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class MinDigits implements Stringable
{
    public function __construct(protected int $value) {}

    public function __toString(): string
    {
        return "min_digits:{$this->value}";
    }
}
