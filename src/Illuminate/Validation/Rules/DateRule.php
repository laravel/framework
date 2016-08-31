<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class DateRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        if ($value instanceof DateTime) {
            return true;
        }

        if ((! is_string($value) && ! is_numeric($value)) || strtotime($value) === false) {
            return false;
        }

        $date = date_parse($value);

        return checkdate($date['month'], $date['day'], $date['year']);
    }
}
