<?php

namespace Illuminate\Database;

use RuntimeException;

class ClassMorphViolationException extends RuntimeException
{
    /**
     * The class name of the affected Eloquent model.
     *
     * @var string
     */
    public $model;

    /**
     * Create a new exception instance.
     *
     * @param  string  $class
     */
    public function __construct($class)
    {
        parent::__construct("No morph map defined for model [{$class}].");

        $this->model = $class;
    }
}
