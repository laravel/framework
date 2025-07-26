<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Size implements Stringable
{
    public function __construct(protected int|float $value)
    {
    }

    public function __toString(): string
    {
        return "size:{$this->value}";
    }
}
