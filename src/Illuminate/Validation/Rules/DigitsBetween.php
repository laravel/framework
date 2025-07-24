<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class DigitsBetween implements Stringable
{
    public function __construct(
        protected int $min,
        protected int $max,
    ) {
    }

    public function __toString(): string
    {
        return "digits_between:{$this->min},{$this->max}";
    }
}
