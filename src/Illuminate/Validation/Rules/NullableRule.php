<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class NullableRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        return true;
    }
}
