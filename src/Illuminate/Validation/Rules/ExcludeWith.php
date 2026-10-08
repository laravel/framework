<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\ParameterizedRule;
use Illuminate\Validation\Rules\Concerns\FormatsParameters;

class ExcludeWith implements ParameterizedRule
{
    use FormatsParameters;

    /**
     * Create a new exclude_with rule instance.
     */
    public function __construct(protected string $anotherField)
    {
    }

    /**
     * Get the rule name followed by its parameters.
     */
    public function toArray(): array
    {
        return ['exclude_with', $this->anotherField];
    }
}
