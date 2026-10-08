<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rules\Concerns\FormatsParameters;
use Stringable;

class Confirmed implements Stringable
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
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        return 'confirmed'.($this->confirmationField !== null ? ':'.$this->formatParameters([$this->confirmationField]) : '');
    }
}
