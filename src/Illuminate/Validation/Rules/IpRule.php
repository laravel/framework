<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class IpRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
}
