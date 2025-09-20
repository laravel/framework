<?php

namespace Illuminate\Database\Eloquent\Casts;

class ArrayObjectCast extends IterableCast
{
    /**
     * Serializes the given value to an array format.
     *
     * @param  mixed  $model
     * @param  string  $key
     * @param  \ArrayObject  $value
     * @param  array  $attributes
     * @return array
     */
    public function serialize($model, string $key, $value, array $attributes)
    {
        return $value->getArrayCopy();
    }
}
