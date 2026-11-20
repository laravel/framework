<?php

namespace Illuminate\JsonSchema\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MatchesEnum implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param  array<int, mixed>  $values
     */
    public function __construct(protected array $values)
    {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, $this->values)) {
            $fail('validation.in')->translate();
        }
    }
}
