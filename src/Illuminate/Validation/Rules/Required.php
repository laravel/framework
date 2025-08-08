<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Required implements Stringable
{
    public function __toString(): string
    {
        return 'required';
    }
}
