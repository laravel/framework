<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Bail implements Stringable
{
    public function __toString(): string
    {
        return 'bail';
    }
}
