<?php

namespace Illuminate\Database\Eloquent;

class FrozenModelException extends \RuntimeException
{
    /**
     * @param  Model  $model
     * @return self
     */
    public static function forFill(Model $model)
    {
        return new self(sprintf("Cannot fill properties on Model [%s] because it is frozen.", $model::class));
    }

    public static function forSetAttribute(Model $model, $attribute)
    {
        return new self(sprintf("Cannot set property [%s] on Model [%s] because it is frozen.", $attribute, $model::class));
    }
}
