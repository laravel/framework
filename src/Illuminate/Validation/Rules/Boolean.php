<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Boolean implements Stringable
{
    public function __toString(): string
    {
        return 'boolean';
    }
}
