<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Declined implements Stringable
{
    public function __toString(): string
    {
        return 'declined';
    }
}
