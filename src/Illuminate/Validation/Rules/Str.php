<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Str implements Stringable
{
    public function __toString(): string
    {
        return 'string';
    }
}
