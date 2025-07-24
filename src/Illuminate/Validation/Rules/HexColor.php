<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class HexColor implements Stringable
{
    public function __toString(): string
    {
        return 'hex_color';
    }
}
