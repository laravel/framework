<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class StartsWith implements Stringable
{
    public function __construct(protected array $values) {}

    public function __toString(): string
    {
        return 'starts_with:' . implode(',', $this->values);
    }
}
