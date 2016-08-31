<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class DigitsBetweenRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    protected $requiredParametersCount = 2;

    /**
     * {@inheritdoc}
     */
    public function mapParameters($parameters)
    {
        return [
            'min' => array_shift($parameters),
            'max' => array_shift($parameters),
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        $length = strlen((string) $value);
        $min = $parameters['min'];
        $max = $parameters['max'];

        return ! preg_match('/[^0-9]/', $value) && $length >= $min && $length <= $max;
    }
}
