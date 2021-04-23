<?php

namespace Illuminate\Contracts\Validation;

interface Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value, $payload);

    /**
     * Get the validation error message.
     *
     * @return string|array
     */
    public function message();
}
