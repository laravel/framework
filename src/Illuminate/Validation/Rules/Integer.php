<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Integer implements Stringable
{
    public function __toString(): string
    {
        return 'integer';
    }
}
