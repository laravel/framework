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
    public function passes($attribute, $value);

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message();
    
    /**
     * Get the validation rule's name.
     * 
     * @return string
     */
    public function name();
}
