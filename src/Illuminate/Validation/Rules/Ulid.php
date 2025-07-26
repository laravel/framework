<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Ulid implements Stringable
{
    public function __toString(): string
    {
        return 'ulid';
    }
}
