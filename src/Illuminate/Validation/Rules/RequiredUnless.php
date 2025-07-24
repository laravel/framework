<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class RequiredUnless implements Stringable
{
    public function __construct(
        protected string $anotherField,
        protected string|null|int|float $value,
    ) {}

    public function __toString(): string
    {
        return 'required_unless:' . $this->anotherField . ',' . ($this->value ?? 'null');
    }
}
