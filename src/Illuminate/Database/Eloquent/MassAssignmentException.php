<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;
use Throwable;

class MassAssignmentException extends RuntimeException
{
    /**
     * The attributes.
     *
     * @var array
     */
    protected $attributes;

    /**
     * The model.
     *
     * @var string
     */
    protected $model;

    /**
     * MassAssignmentException constructor.
     *
     * @param  mixed  $attributes
     * @param  string  $model
     * @param  int  $code
     * @param  \Throwable|null  $previous
     * @return void
     */
    public function __construct($attributes, $model, $code = 0, Throwable $previous = null)
    {
        $this->attributes = is_array($attributes) ? $attributes : [$attributes];
        $this->model = $model;

        $message = sprintf(
            'Add [%s] to fillable property to allow mass assignment on [%s].',
            implode(', ', $this->attributes), $this->model
        );

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the attributes.
     *
     * @return array
     */
    public function attributes()
    {
        return $this->attributes;
    }

    /**
     * Get the model.
     *
     * @return string
     */
    public function model()
    {
        return $this->model;
    }
}
