<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rules\Concerns\FormatsParameters;
use Stringable;

class RequiredIfDeclined implements Stringable
{
    use FormatsParameters;

    /**
     * Create a new required_if_declined rule instance.
     */
    public function __construct(protected string $field)
    {
    }

    /**
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        return 'required_if_declined:'.$this->formatParameters([$this->field]);
    }
}
