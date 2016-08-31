<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class NumericRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        return is_numeric($value);
    }
}
