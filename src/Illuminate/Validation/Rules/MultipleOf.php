<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class MultipleOf implements Stringable
{
    public function __construct(protected int|float $value)
    {
    }

    public function __toString(): string
    {
        return 'multiple_of:'.$this->value;
    }
}
