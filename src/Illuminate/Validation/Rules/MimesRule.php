<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class MimesRule extends Rule
{
    use Traits\ValidFileInstance;

    public function mapParameters($parameters)
    {
        return [
            'mimes' => $parameters,
        ];
    }

    public function passes($attribute, $value, $parameters, $validator)
    {
        if (! $this->isAValidFileInstance($value)) {
            return false;
        }

        return $value->getPath() != '' && in_array($value->guessExtension(), $parameters['mimes']);
    }
}
