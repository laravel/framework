<?php

namespace Illuminate\Collections;

use Illuminate\Support\Traits\Macroable;

class Obj
{
    use Macroable;

    /**
     * Recursively convert all objects to arrays.
     *
     * @param  mixed  $object
     * @return array
     */
    public static function deepArrayify($object)
    {
        if (is_object($object)) {
            $object = get_object_vars($object);
        }

        return array_map(function ($value) {
            if (is_object($value) || is_array($value)) {
                return self::deepArrayify($value);
            }

            return $value;
        }, $object);
    }
}
