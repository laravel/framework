<?php

namespace Illuminate\Support;

use Illuminate\Database\Eloquent\Model;

if (! function_exists('Illuminate\Support\is_model')) {
    /**
     * Determine if the given value is a model.
     *
     * @internal
     *
     * @template TModel
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
     * @internal
     *
     * @template TModel
     *
     * @param  TModel  $model
     * @param  callable(TModel): mixed  $callback
     * @return mixed
     */
    function model_key($model, $callback = null)
    {
        if (is_model($model)) {
            return $model->getKey();
        }

        return with($model, $callback);
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
