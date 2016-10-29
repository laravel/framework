<?php

namespace Illuminate\Contracts\Validation;

interface Rule
{
    /**
     * Apply rule to validator.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @param  string  $field
     */
    public function apply(Validator $validator, $field);
}
