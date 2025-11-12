<?php

namespace Illuminate\Support;

use Illuminate\Database\Eloquent\Model;

if (! function_exists('Illuminate\Support\is_model')) {
    /**
     * Determine if the given value is a model.
     *
     * @param  TModel  $model
     * @return bool
     */
    function is_model($model)
    {
        return $model instanceof Model;
    }
}

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
            is_model($model) => $model->getKey(),

            default => $model ?? value($default, $model),
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
