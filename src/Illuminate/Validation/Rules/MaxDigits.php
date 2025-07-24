<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class MaxDigits implements Stringable
{
    public function __construct(protected int $value) {}

    public function __toString(): string
    {
        return "max_digits:{$this->value}";
    }
}
