<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class ProhibitedIfAccepted implements Stringable
{
    public function __construct(protected string $field)
    {
    }

    public function __toString(): string
    {
        return 'prohibited_if_accepted:'.$this->field;
    }
}
