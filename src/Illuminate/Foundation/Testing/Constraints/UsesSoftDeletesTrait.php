<?php

namespace Illuminate\Foundation\Testing\Constraints;

use PHPUnit\Framework\Constraint\Constraint;

class UsesSoftDeletesTrait extends Constraint
{
    /**
     * The Eloquent Model string class.
     *
     * @var string
     */
    protected $model;

    /**
     * Create a new constraint instance.
     *
     * @param  string  $model
     * @return void
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Check if the model has the SoftDeletes::getDeletedAtColumn method.
     *
     * @param  string  $model
     * @return bool
     */
    public function matches($model)
    {
        return method_exists($model, 'getDeletedAtColumn');
    }

    /**
     * Get the description of the failure.
     *
     * @param  string $model
     * @return string
     */
    public function failureDescription($model)
    {
        return 'the given model uses the SoftDeletes trait';
    }

    /**
     * Get a string representation of the object.
     *
     * @return string
     */
    public function toString()
    {
        return json_encode($this->model);
    }
}
