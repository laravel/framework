<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class ExcludeWithout implements Stringable
{
    public function __construct(protected string $anotherField) {}

    public function __toString(): string
    {
        return "exclude_without:{$this->anotherField}";
    }
}
