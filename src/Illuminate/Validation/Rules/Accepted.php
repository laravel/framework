<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Accepted implements Stringable
{
    public function __toString(): string
    {
        return 'accepted';
    }
}
