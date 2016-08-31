<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class JsonRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        if (! is_scalar($value) && ! method_exists($value, '__toString')) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
