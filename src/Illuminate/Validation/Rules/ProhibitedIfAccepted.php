<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class prohibitedIfAccepted implements Stringable
{
    public function __construct(protected array $fields) {}

    public function __toString(): string
    {
        return 'prohibited_if_accepted:' . implode(',', $this->fields);
    }
}
