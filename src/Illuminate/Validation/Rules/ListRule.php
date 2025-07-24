<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class ListRule implements Stringable
{
    public function __toString(): string
    {
        return 'list';
    }
}
