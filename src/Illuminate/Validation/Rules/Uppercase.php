<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Uppercase implements Stringable
{
    public function __toString(): string
    {
        return 'uppercase';
    }
}
