<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rules\Concerns\FormatsParameters;
use Stringable;

class ProhibitedIfDeclined implements Stringable
{
    use FormatsParameters;

    /**
     * Create a new prohibited_if_declined rule instance.
     */
    public function __construct(protected string $field)
    {
    }

    /**
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        return 'prohibited_if_declined:'.$this->formatParameters([$this->field]);
    }
}
