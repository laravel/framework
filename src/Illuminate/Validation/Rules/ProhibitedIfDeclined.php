<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class ProhibitedIfDeclined implements Stringable
{
    public function __construct(protected string $field)
    {
    }

    public function __toString(): string
    {
        return 'prohibited_if_declined:'.$this->field;
    }
}
