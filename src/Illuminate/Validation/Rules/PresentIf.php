<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class PresentIf implements Stringable
{
    public function __construct(
        protected string $anotherField,
        protected string|null|int|float $value,
    ) {}

    public function __toString(): string
    {
        return 'present_if:' . $this->anotherField . ',' . ($this->value ?? 'null');
    }
}
