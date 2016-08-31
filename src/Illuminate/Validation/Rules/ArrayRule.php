<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class ArrayRule extends Rule
{
    public function passes($attribute, $value, $parameters, $validator)
    {
        return is_array($value);
    }
}
