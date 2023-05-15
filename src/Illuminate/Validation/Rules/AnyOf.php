<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AnyOf implements Rule
{
    /**
     * Create a new any_of validation rule that returns true if any of the rules is true.
     *
     * @return void
     */
    public function __construct(protected array $rules)
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $attribute = str_replace('.', Str::random(), $attribute);

        foreach ($this->rules as $rule) {
            $data = [
                $attribute => $value,
            ];
            $rules = [
                $attribute => $rule,
            ];

            if (Validator::make($data, $rules)->passes()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message(): array
    {
        $message = trans('validation.any_of');

        return $message === 'validation.any_of'
            ? ['None of the specified field rules is true.']
            : $message;
    }
}
