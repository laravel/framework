<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;

class Boolean implements Rule
{
    /**
     * The name of the rule.
     */
    protected string $rule = 'boolean';

    /**
     * The values to check.
     *
     * @var array
     */
    protected array $acceptable = [true, false, 'true', 'false', 1, 0, '1', '0'];

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     *
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return in_array($value, $this->acceptable, true);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return trans('validation.boolean');
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->rule;
    }
}
