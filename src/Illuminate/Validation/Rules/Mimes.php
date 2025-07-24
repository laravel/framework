<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Mimes implements Stringable
{
    public function __construct(protected array $extensions)
    {
    }

    public function __toString(): string
    {
        return 'mimes:'.implode(',', $this->extensions);
    }
}
