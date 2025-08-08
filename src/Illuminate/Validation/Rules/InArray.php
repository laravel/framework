<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class InArray implements Stringable
{
    public function __construct(protected string $otherField)
    {
    }

    public function __toString(): string
    {
        return "in_array:{$this->otherField}";
    }
}
