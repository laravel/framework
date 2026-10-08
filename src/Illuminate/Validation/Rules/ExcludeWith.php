<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rules\Concerns\FormatsParameters;
use Stringable;

class ExcludeWith implements Stringable
{
    use FormatsParameters;

    /**
     * Create a new exclude_with rule instance.
     */
    public function __construct(protected string $anotherField)
    {
    }

    /**
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        return 'exclude_with:'.$this->formatParameters([$this->anotherField]);
    }
}
