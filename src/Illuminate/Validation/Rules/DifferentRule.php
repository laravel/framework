<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class DifferentRule extends Rule
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
            'other_attribute' => array_shift($parameters)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        $matching = $validator->getMatchingAttribute($attribute, $parameters['other_attribute']);
        $other = Arr::get($validator->getData(), $matching);

        return isset($other) && $value !== $other;
    }
}
