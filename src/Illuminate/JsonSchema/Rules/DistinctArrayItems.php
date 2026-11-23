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
        $items = array_map(
            static::normalize(...), array_values(array_filter((array) $value, is_array(...)))
        );

        foreach ($items as $index => $item) {
            if (in_array($item, array_slice($items, $index + 1), true)) {
                $fail('validation.distinct')->translate();

                return;
            }
        }
    }

    /**
     * Sort the keys of every object in the value so that key order does not affect strict comparison.
     */
    public static function normalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $value = array_map(static::normalize(...), $value);

        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return $value;
    }
}
