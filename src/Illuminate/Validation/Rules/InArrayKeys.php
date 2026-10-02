<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class InArrayKeys implements Stringable
{
    protected array $keys;

    public function __construct(array|string $keys)
    {
        $this->keys = is_array($keys) ? $keys : func_get_args();
    }

    public function __toString(): string
    {
        return 'in_array_keys:'.implode(',', $this->keys);
    }
}
