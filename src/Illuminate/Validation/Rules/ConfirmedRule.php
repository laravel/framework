<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class ConfirmedRule extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        $otherAttribute = $attribute.'_confirmation';

        return $validator->validateRule('same', $attribute, [
            'other_attribute' => $otherAttribute
        ]);
    }
}
