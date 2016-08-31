<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class BooleanRule extends Rule
{
    public function passes($attribute, $value, $parameters, $validator)
    {
        $acceptable = [true, false, 0, 1, '0', '1'];

        return in_array($value, $acceptable, true);
    }
}
