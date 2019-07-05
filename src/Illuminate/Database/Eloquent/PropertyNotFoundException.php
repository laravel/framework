<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class PropertyNotFoundException extends RuntimeException
{
    /**
     * The name of the affected Eloquent model.
     *
     * @var string
     */
    public $model;

    /**
     * The name of the property.
     *
     * @var string
     */
    public $property;

    /**
     * Create a new exception instance.
     *
     * @param  mixed  $model
     * @param  string  $property
     * @return static
     */
    public static function make($model, $property)
    {
        $class = get_class($model);

        $instance = new static("Call to undefined property [{$property}] on model [{$class}].");

        $instance->model = $model;
        $instance->property = $property;

        return $instance;
    }
}
