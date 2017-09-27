<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class RelationNotFoundException extends RuntimeException
{
    /**
     * Name of the affected Eloquent model.
     *
     * @var string
     */
    protected $model;

    /**
     * Name of the affected relation.
     *
     * @var string
     */
    protected $relation;

    /**
     * Create a new exception instance.
     *
     * @param  mixed  $model
     * @param  string  $relation
     * @return static
     */
    public static function make($model, $relation)
    {
        $this->model = $model;
        $this->relation = $relation;

        $class = get_class($model);

        return new static("Call to undefined relationship [{$relation}] on model [{$class}].");
    }

    /**
     * Get the affected Eloquent model.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get the affected Eloquent model IDs.
     *
     * @return int|array
     */
    public function getRelation()
    {
        return $this->relation;
    }
}
