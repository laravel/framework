<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class ObjectCastingException extends RuntimeException
{
    /**
     * Create a new JSON encoding exception for an attribute.
     *
     * @param  mixed  $model
     * @param  mixed  $key
     * @param  string  $castingClass
     * @param  string  $message
     * @return static
     */
    public static function forAttribute($model, $key, $castingClass, $message)
    {
        $class = get_class($model);

        return new static("Unable to cast attribute [{$key}] for model [{$class}] to [$castingClass]: {$message}.");
    }
}