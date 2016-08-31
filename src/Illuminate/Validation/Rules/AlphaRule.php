<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class AlphaRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        return is_string($value) && preg_match('/^[\pL\pM]+$/u', $value);
    }
}
