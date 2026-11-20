<?php

namespace Illuminate\JsonSchema\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

class MatchesAnyOf implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param  array<int, array<string, array<int, mixed>>>  $branches  The rules for each branch, keyed under "value".
     */
    public function __construct(protected array $branches)
    {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($this->branches as $rules) {
            if (Validator::make(['value' => $value], $rules)->passes()) {
                return;
            }
        }

        $fail('validation.any_of')->translate();
    }
}
