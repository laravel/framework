<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Nullable implements Stringable
{
    public function __toString(): string
    {
        return 'nullable';
    }
}
