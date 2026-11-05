<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\ParameterizedRule;
use Illuminate\Validation\Rules\Concerns\FormatsParameters;

class Confirmed implements ParameterizedRule
{
    use FormatsParameters;

    protected ?string $confirmationField = null;

    /**
     * Specify the confirmation field.
     */
    public function customField(string $confirmationField): static
    {
        $this->confirmationField = $confirmationField;

        return $this;
    }

    /**
     * Get the rule name followed by its parameters.
     */
    public function toArray(): array
    {
        return $this->confirmationField === null ? ['confirmed'] : ['confirmed', $this->confirmationField];
    }
}
