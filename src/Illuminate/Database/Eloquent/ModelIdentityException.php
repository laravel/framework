<?php

declare(strict_types=1);

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class ModelIdentityException extends RuntimeException
{
    /**
     * Create a new model identity exception for the model.
     *
     * @param  mixed  $model
     *
     * @return static
     */
    public static function forModel($model)
    {
        return new static('Model ['.get_class($model).'] is not identifiable');
    }
}
