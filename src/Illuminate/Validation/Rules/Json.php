<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Json implements Stringable
{
    public function __toString(): string
    {
        return 'json';
    }
}
