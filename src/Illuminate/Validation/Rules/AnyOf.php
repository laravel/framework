<?php

namespace Illuminate\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AnyOf implements ValidationRule
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
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $attribute = str_replace('.', Str::random(), $attribute);
        $passes = false;

        foreach ($this->rules as $rule) {
            $data = [
                $attribute => $value,
            ];
            $rules = [
                $attribute => $rule,
            ];

            if (Validator::make($data, $rules)->passes()) {
                $passes = true;
                break;
            }
        }

        if (! $passes) {
            $fail($this->message());
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        $message = trans('validation.any_of');

        return $message === 'validation.any_of'
            ? 'None of the specified field rules is true.'
            : $message;
    }
}
