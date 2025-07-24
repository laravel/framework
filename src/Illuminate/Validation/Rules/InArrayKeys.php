<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class InArrayKeys implements Stringable
{
    public function __construct(protected array $keys) {}

    public function __toString(): string
    {
        return 'in_array_keys:' . implode(',', $this->keys);
    }
}
