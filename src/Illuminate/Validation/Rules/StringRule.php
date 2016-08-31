<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class StringRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        return is_string($value);
    }
}
