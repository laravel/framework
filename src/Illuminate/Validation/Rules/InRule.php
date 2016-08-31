<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class InRule extends Rule
{
    public function passes($attribute, $value, $parameters, $validator)
    {
        if (is_array($value) && $validator->getRules($attribute)->has('array')) {
            foreach ($value as $element) {
                if (is_array($element)) {
                    return false;
                }
            }

            return count(array_diff($value, $parameters)) == 0;
        }

        return ! is_array($value) && in_array((string) $value, $parameters);
    }
}
