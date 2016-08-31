<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class AlphaDashRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\pN_-]+$/u', $value) > 0;
    }
}
