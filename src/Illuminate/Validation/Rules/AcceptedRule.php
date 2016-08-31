<?php

namespace Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;

class AcceptedRule extends Rule
{
    public function passes($attribute, $value, $parameters, $validator)
    {
        $acceptable = ['yes', 'on', '1', 1, true, 'true'];

        return $validator->validateRule('required', $attribute) && in_array($value, $acceptable, true);
    }
}
