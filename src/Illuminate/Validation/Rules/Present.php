<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Present implements Stringable
{
    public function __toString(): string
    {
        return 'present';
    }
}
