<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class PresentRule extends Rule
{
    protected $implicit = true;

    public function passes($attribute, $value, $parameters, $validator)
    {
        return Arr::has($validator->getRawData(), $attribute);
    }
}
