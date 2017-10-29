<?php

namespace Illuminate\Contracts\Validation;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;

interface Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  ValidatorContract  $validator
     * @return bool
     */
    public function passes($attribute, $value, ValidatorContract $validator);

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message();
}
