<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class DigitsRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    protected $requiredParametersCount = 1;

    /**
     * {@inheritdoc}
     */
    public function mapParameters($parameters)
    {
        return [
            'length' => array_shift($parameters),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        $length = strlen((string) $value);
        $min = $parameters['length'];

        return ! preg_match('/[^0-9]/', $value) && $length == $parameters['length'];
    }
}
