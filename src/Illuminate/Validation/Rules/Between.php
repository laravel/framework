<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Between implements Stringable
{
    public function __construct(
        protected int|float $min,
        protected int|float $max
    ) {
    }

    public function __toString(): string
    {
        return "between:{$this->min},{$this->max}";
    }
}
