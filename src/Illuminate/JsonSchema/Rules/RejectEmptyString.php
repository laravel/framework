<?php

namespace Illuminate\JsonSchema\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RejectEmptyString implements ValidationRule
{
    /**
     * Indicates whether the rule should be implicit.
     *
     * Laravel skips non-implicit rules for an empty string, so only an implicit rule can refuse one.
     *
     * @var bool
     */
    public $implicit = true;

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === '') {
            $fail('validation.filled')->translate();
        }
    }
}
