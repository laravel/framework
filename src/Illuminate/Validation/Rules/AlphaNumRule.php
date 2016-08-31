<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class AlphaNumRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\pN]+$/u', $value) > 0;
    }
}
