<?php

namespace Illuminate\Validation\FailureFormatters;

use Illuminate\Support\Str;
use Illuminate\Contracts\Validation\Validator;

class Rule implements FailureFormatterInterface
{
    /**
     * Get formatted message.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @param  string $attribute
     * @param  string $rule
     * @param  array $parameters
     * @return string
     */
    public function message(Validator $validator, $attribute, $rule, $parameters)
    {
        return Str::snake($rule);
    }
}
