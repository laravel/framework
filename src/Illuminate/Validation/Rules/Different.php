<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Different implements Stringable
{
    public function __construct(protected string $field) {}

    public function __toString(): string
    {
        return "different:{$this->field}";
    }
}
