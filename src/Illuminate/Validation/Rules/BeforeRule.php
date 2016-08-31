<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;
use DateTimeInterface;

class BeforeRule extends Rule
{
    use Traits\DateValidation;

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
            'date' => array_shift($parameters),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        if (! is_string($value) && ! is_numeric($value) && ! $value instanceof DateTimeInterface) {
            return false;
        }

        $afterAttribute = $validator->getMatchingAttribute($attribute, $parameters['date']);

        if ($format = $this->getDateFormat($attribute, $validator)) {
            $afterAttribute = $validator->getValue($afterAttribute) ?: $afterAttribute;

            return $this->validateWithFormat($format, $afterAttribute, $value);
        }

        if (! $date = $this->getDateTimestamp($afterAttribute)) {
            $date = $this->getDateTimestamp($validator->getValue($afterAttribute));
        }

        return $this->getDateTimestamp($value) < $date;
    }
}
