<?php

namespace Illuminate\Validation\FailureFormatters;

use Illuminate\Contracts\Validation\Validator;

class Message extends FailureFormatter
{
    /**
     * Get formatted message.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    public function message(Validator $validator, $attribute, $rule, $parameters)
    {
        return $validator->makeReplacements(
            $validator->getMessage($attribute, $rule), $attribute, $rule, $parameters
        );
    }
}