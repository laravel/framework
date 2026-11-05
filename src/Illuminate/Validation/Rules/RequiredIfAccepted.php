<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\ParameterizedRule;
use Illuminate\Validation\Rules\Concerns\FormatsParameters;

class RequiredIfAccepted implements ParameterizedRule
{
    use FormatsParameters;

    /**
     * Create a new required_if_accepted rule instance.
     */
    public function __construct(protected string $field)
    {
    }

    /**
     * Get the rule name followed by its parameters.
     */
    public function toArray(): array
    {
        return ['required_if_accepted', $this->field];
    }
}
