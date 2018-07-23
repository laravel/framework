<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class RelationNotFoundException extends RuntimeException
{
    /**
     * The name of the affected Eloquent model.
     *
     * @var string
     */
    public $model;

    /**
     * The name of the relation.
     *
     * @var string
     */
    public $relation;

    /**
     * Create a new exception instance.
     *
     * @param  mixed  $model
     * @param  string  $relation
     * @return static
     */
    public static function make($model, $relation)
    {
        $class = get_class($model);

        $instance = new static("Call to undefined relationship [{$relation}] on model [{$class}].");

        $instance->model = $model;
        $instance->relation = $relation;

        return $instance;
    }
}
