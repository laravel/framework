<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rules\Concerns\FormatsParameters;
use Stringable;

class ProhibitedIfAccepted implements Stringable
{
    use FormatsParameters;

    /**
     * Create a new prohibited_if_accepted rule instance.
     */
    public function __construct(protected string $field)
    {
    }

    /**
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        return 'prohibited_if_accepted:'.$this->formatParameters([$this->field]);
    }
}
