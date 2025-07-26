<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Prohibited implements Stringable
{
    public function __toString(): string
    {
        return 'prohibited';
    }
}
