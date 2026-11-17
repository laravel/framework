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
        if (! $this->matches($value)) {
            $fail('validation.in')->translate();
        }
    }

    /**
     * Determine if the value is one of the enumerated values.
     *
     * Arrays are compared strictly by content, since loose comparison treats "1" and "01" (or true and any array) as equal.
     */
    protected function matches(mixed $value): bool
    {
        if (! is_array($value)) {
            return in_array($value, array_filter($this->values, fn (mixed $allowed) => ! is_array($allowed)));
        }

        $value = DistinctArrayItems::normalize($value);

        foreach ($this->values as $allowed) {
            if (is_array($allowed) && DistinctArrayItems::normalize($allowed) === $value) {
                return true;
            }
        }

        return false;
    }
}
