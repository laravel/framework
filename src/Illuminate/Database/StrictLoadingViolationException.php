<?php

namespace Illuminate\Database;

use RuntimeException;

class StrictLoadingViolationException extends RuntimeException
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
     * @param  object  $model
     * @param  string  $relation
     * @return static
     */
    public function __construct($model, $relation)
    {
        $class = get_class($model);

        parent::__construct("Trying to lazy load [{$relation}] in model [{$class}] is restricted.");

        $this->model = $class;
        $this->relation = $relation;
    }
}
