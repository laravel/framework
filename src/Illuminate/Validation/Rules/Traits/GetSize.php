<?php

namespace Illuminate\Validation\Rules\Traits;

use Symfony\Component\HttpFoundation\File\File;

trait GetSize
{
    /**
     * Get the size of an attribute.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @param  \Illuminate\Validation\Validator $validator
     * @return mixed
     */
    protected function getSize($attribute, $value, $validator)
    {
        $hasNumeric = $validator->getRules($attribute)->has($validator->getNumericRules());

        // This method will determine if the attribute is a number, string, or file and
        // return the proper size accordingly. If it is a number, then number itself
        // is the size. If it is a file, we take kilobytes, and for a string the
        // entire length of the string will be considered the attribute size.
        if (is_numeric($value) && $hasNumeric) {
            return $value;
        } elseif (is_array($value)) {
            return count($value);
        } elseif ($value instanceof File) {
            return $value->getSize() / 1024;
        }

        return mb_strlen($value);
    }
}
