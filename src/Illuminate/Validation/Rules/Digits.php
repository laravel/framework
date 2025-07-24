<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Digits implements Stringable
{
    public function __construct(protected int $length) {}

    public function __toString(): string
    {
        return "digits:{$this->length}";
    }
}
