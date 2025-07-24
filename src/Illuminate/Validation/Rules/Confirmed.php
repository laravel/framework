<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Confirmed implements Stringable
{
    protected ?string $confirmationField = null;

    public function customField(string $confirmationField)
    {
        $this->confirmationField = $confirmationField;

        return $this;
    }

    public function __toString(): string
    {
        return 'confirmed' . ($this->confirmationField ? ":{$this->confirmationField}" : '');
    }
}
