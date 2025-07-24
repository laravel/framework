<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class ActiveUrl implements Stringable
{
    public function __toString(): string
    {
        return 'active_url';
    }
}
