<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class EndsWith implements Stringable
{
    public function __construct(protected array $values)
    {
    }

    public function __toString(): string
    {
        return 'ends_with:'.implode(',', $this->values);
    }
}
