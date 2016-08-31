<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class DateFormatRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    protected $requiredParameters = 1;

    /**
     * {@inheritdoc}
     */
    public function mapParameters($parameters)
    {
        return [
            'format' => array_shift($parameters),
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

        $parsed = date_parse_from_format($parameters['format'], $value);

        return $parsed['error_count'] === 0 && $parsed['warning_count'] === 0;
    }
}
