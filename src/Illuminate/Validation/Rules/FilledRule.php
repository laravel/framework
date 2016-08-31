<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class FilledRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        if (Arr::has($validator->getRawData(), $attribute)) {
            return $validator->validateRule('required', $attribute);
        }

        return true;
    }
}
