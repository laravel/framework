<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class ImageRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        return $validator->validateRule('mimes', $attribute, ['jpeg', 'png', 'gif', 'bmp', 'svg']);
    }
}
