<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Exclude implements Stringable
{
    public function __toString(): string
    {
        return 'exclude';
    }
}
