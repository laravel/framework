<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Sometimes implements Stringable
{
    public function __toString(): string
    {
        return 'sometimes';
    }
}
