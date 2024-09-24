<?php

use Illuminate\Contracts\Validation\ValidationRule;

use function PHPStan\Testing\assertType;

new class implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        assertType('Closure(string, string|null=): Illuminate\Translation\PotentiallyTranslatedString', $fail);
    }
};
