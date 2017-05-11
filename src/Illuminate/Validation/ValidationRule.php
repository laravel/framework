<?php

namespace Illuminate\Validation;

interface ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value);

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message();
}
