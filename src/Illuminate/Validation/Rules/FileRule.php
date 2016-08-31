<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class FileRule extends Rule
{
    use Traits\ValidFileInstance;

    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        return $this->isAValidFileInstance($value);
    }
}
