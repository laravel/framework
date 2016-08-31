<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class BetweenRule extends Rule
{
    use Traits\GetSize;

    protected $requiredParametersCount = 2;

    public function mapParameters($parameters)
    {
        return array_combine(['min', 'max'], $parameters);
    }

    public function passes($attribute, $value, $parameters, $validator)
    {
        $size = $this->getSize($attribute, $value, $validator);

        return $size >= $parameters['min'] && $size <= $parameters['max'];
    }
}
