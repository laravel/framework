<?php

namespace Illuminate\Contracts\Validation;

interface Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string                                     $attribute
     * @param  mixed                                      $value
     * @param  \Illuminate\Contracts\Validation\Validator $validator
     * @return bool
     */
    public function passes($attribute, $value, $validator);

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message();
}
