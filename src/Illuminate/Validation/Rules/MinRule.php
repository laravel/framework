<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class MinRule extends Rule
{
    use Traits\GetSize;

    protected $requiredParametersCount = 1;

    public function mapParameters($parameters)
    {
        return array_combine(['min'], $parameters);
    }

    public function passes($attribute, $value, $parameters, $validator)
    {
        return $this->getSize($attribute, $value, $validator) >= $parameters['min'];
    }
}
