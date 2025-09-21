<?php

namespace Illuminate\Database\Eloquent\Casts;

use ArrayObject;

class ArrayObjectCast extends IterableCast
{
    /**
     * Instances the target iterable class.
     *
     * @param  \Illuminate\Support\Collection $data
     * @return \Illuminate\Database\Eloquent\Casts\ArrayObject
     */
    protected function makeIterableObject($data)
    {
        return new ($this->using)($data->all(), ArrayObject::ARRAY_AS_PROPS);
    }

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
