<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Lowercase implements Stringable
{
    public function __toString(): string
    {
        return 'lowercase';
    }
}
