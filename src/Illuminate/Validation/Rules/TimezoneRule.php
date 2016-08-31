<?php

namespace Illuminate\Validation\Rules;

use Exception;
use DateTimeZone;
use Illuminate\Validation\Rule;

class TimezoneRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        try {
            new DateTimeZone($value);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
