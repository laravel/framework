<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class RequiredIfAccepted implements Stringable
{
    public function __construct(protected string $field)
    {
    }

    public function __toString(): string
    {
        return 'required_if_accepted:'.$this->field;
    }
}
