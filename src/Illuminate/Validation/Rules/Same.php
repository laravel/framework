<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Same implements Stringable
{
    public function __construct(protected string $field) {}

    public function __toString(): string
    {
        return "same:{$this->field}";
    }
}
