<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrRule implements Rule
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
     * @return bool
     */
    public function passes($attribute, $value): bool
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

        return Validator::make($data, $rules)->passes()
            || Validator::make($data, $orRules)->passes();
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
