<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MaxRule extends Rule
{
    use Traits\GetSize;

    protected $requiredParametersCount = 1;

    public function mapParameters($parameters)
    {
        return array_combine(['max'], $parameters);
    }

    public function passes($attribute, $value, $parameters, $validator)
    {
        if ($value instanceof UploadedFile && ! $value->isValid()) {
            return false;
        }

        return $this->getSize($attribute, $value, $validator) <= $parameters['max'];
    }
}
