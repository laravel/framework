<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class RequiredArrayKeys implements Stringable
{
    public function __construct(protected array $keys) {}

    public function __toString(): string
    {
        return 'required_array_keys:' . implode(',', $this->keys);
    }
}
