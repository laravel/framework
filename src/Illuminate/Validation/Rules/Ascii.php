<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Ascii implements Stringable
{
    public function __toString(): string
    {
        return 'ascii';
    }
}
