<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class ImmutableAttributeException extends RuntimeException
{
    /**
     * The model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $model;

    /**
     * The immutable attributes that were changed.
     *
     * @var array<int, string>
     */
    public $attributes;

    /**
     * Create a new immutable attribute exception instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array<int, string>  $attributes
     */
    public function __construct($model, array $attributes)
    {
        $this->model = $model;
        $this->attributes = $attributes;

        parent::__construct(sprintf(
            count($attributes) === 1
                ? 'The attribute [%s] on model [%s] is immutable and cannot be updated.'
                : 'The attributes [%s] on model [%s] are immutable and cannot be updated.',
            implode(', ', $attributes), get_class($model)
        ));
    }
}
