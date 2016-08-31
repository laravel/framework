<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class SizeRule extends Rule
{
    use Traits\GetSize;

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
            'size' => array_shift($parameters)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        return $this->getSize($attribute, $value, $validator) == $parameters['size'];
    }
}
