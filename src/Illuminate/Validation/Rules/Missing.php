<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Missing implements Stringable
{
    public function __toString(): string
    {
        return 'missing';
    }
}
