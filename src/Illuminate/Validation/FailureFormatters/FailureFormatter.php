<?php

namespace Illuminate\Validation\FailureFormatters;

use Illuminate\Contracts\Validation\Validator;

abstract class FailureFormatter
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
    abstract public function message(Validator $validator, $attribute, $rule, $parameters);
}