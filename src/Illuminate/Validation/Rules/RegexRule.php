<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class RegexRule extends Rule
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
            'pattern' => array_shift($parameters),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match($parameters['pattern'], $value) > 0;
    }
}
