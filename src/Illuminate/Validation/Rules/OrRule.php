<?php

namespace Illuminate\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrRule implements ValidationRule
{
    /**
     * Create a new or validation rule based on two rules.
     *
     * @return void
     */
    public function __construct(protected mixed $rule, protected mixed $orRule)
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  Closure  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $attribute = str_replace('.', Str::random(), $attribute);

        $data = [
            $attribute => $value,
        ];
        $rules = [
            $attribute => $this->rule,
        ];
        $orRules = [
            $attribute => $this->orRule,
        ];

        if (Validator::make($data, $rules)->fails() && Validator::make($data, $orRules)->fails()) {
            $fail($this->message());
        }
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message(): array
    {
        $message = trans('validation.or_rule');

        return $message === 'validation.or_rule'
            ? ['None of the specified field rules is true.']
            : [$message];
    }
}
