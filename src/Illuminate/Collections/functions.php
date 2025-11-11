<?php

namespace Illuminate\Support;

if (! function_exists('Illuminate\Support\model_key')) {
    /**
     * Return the key for the given model.
     *
     * @param  TModel  $model
     * @param  TDefault|callable(TModel): TDefault  $default
     * @return ($model is empty ? TDefault : mixed)
     */
    function model_key($model, $default = null)
    {
        return match (true) {
            $model instanceof \Illuminate\Database\Eloquent\Model => $model->getKey(),

            default => $value ?? value($default),
        };
    }
}

if (! function_exists('Illuminate\Support\enum_value')) {
    /**
     * Return a scalar value for the given value that might be an enum.
     *
     * @internal
     *
     * @template TValue
     * @template TDefault
     *
     * @param  TValue  $value
     * @param  TDefault|callable(TValue): TDefault  $default
     * @return ($value is empty ? TDefault : mixed)
     */
    function enum_value($value, $default = null)
    {
        return match (true) {
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \UnitEnum => $value->name,

            default => $value ?? value($default),
        };
    }
}
