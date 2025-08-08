<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Filled implements Stringable
{
    public function __toString(): string
    {
        return 'filled';
    }
}
