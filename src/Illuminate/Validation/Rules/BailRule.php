<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class BailRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        return true;
    }
}
