<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\File\File;

class RequiredRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    protected $implicit = true;

    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif ((is_array($value) || $value instanceof Countable) && count($value) < 1) {
            return false;
        } elseif ($value instanceof File) {
            return (string) $value->getPath() != '';
        }

        return true;
    }
}
