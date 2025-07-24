<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Prohibits implements Stringable
{
    public function __construct(protected array $fields) {}

    public function __toString(): string
    {
        return 'prohibits:' . implode(',', $this->fields);
    }
}
