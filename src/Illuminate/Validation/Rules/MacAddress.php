<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class MacAddress implements Stringable
{
    public function __toString(): string
    {
        return 'mac_address';
    }
}
