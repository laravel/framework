<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class InvalidCastException extends RuntimeException
{
    /**
     * The name of the affected Eloquent model.
     *
     * @var object
     */
    public $model;

    /**
     * The name of the column.
     *
     * @var string
     */
    public $column;

    /**
     * The name of the cast type.
     *
     * @var string
     */
    public $castType;

    /**
     * Create a new exception instance.
     *
     * @param  object  $model
     * @param  string  $column
     * @param  string  $castType
     * @return static
     */
    public static function make($model, $column, $castType)
    {
        $class = get_class($model);

        $instance = new static("Call to undefined cast [{$castType}] on column [{$column}] in model [{$class}].");

        $instance->model = $model;
        $instance->column = $column;
        $instance->castType = $castType;

        return $instance;
    }
}
