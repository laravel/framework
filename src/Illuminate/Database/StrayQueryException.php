<?php

namespace Illuminate\Database;

use RuntimeException;

class StrayQueryException extends RuntimeException
{
    /**
     * The name of the affected Eloquent model.
     *
     * @var string
     */
    public $model;

    /**
     * Create a new exception instance.
     *
     * @param  object  $model
     * @return void
     */
    public function __construct($model)
    {
        $class = get_class($model);

        parent::__construct("Stray query detected on model [{$class}] but stray queries are being prevented.");

        $this->model = $class;
    }
}
