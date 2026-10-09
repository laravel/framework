<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\ParameterizedRule;
use Illuminate\Validation\Rules\Concerns\FormatsParameters;

class ProhibitedIfDeclined implements ParameterizedRule
{
    use FormatsParameters;

    /**
     * Create a new prohibited_if_declined rule instance.
     */
    public function __construct(protected string $field)
    {
    }

    /**
     * Get the rule name followed by its parameters.
     */
    public function toArray(): array
    {
        return ['prohibited_if_declined', $this->field];
    }
}
