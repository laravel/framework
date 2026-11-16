<?php

namespace Illuminate\JsonSchema\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DistinctArrayItems implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * Laravel's "distinct" rule does not compare array items, so this rule compares them by content.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $items = array_values(array_filter((array) $value, is_array(...)));

        foreach ($items as $index => $item) {
            if (in_array($item, array_slice($items, $index + 1))) {
                $fail('validation.distinct')->translate();

                return;
            }
        }
    }
}
