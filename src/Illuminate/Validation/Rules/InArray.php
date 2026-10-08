<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rules\Concerns\FormatsParameters;
use Stringable;

class InArray implements Stringable
{
    use FormatsParameters;

    /**
     * Create a new in_array rule instance.
     */
    public function __construct(protected string $otherField)
    {
    }

    /**
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        return 'in_array:'.$this->formatParameters([$this->otherField]);
    }
}
