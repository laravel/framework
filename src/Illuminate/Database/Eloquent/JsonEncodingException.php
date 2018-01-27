<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Support\JsonEncodingException as BaseJsonEncodingException;

class JsonEncodingException extends BaseJsonEncodingException
{
    /**
     * Create a new JSON encoding exception for the model.
     *
     * @param  mixed  $model
     * @param  string  $message
     * @return static
     */
    public static function forModel($model, $message)
    {
        return new static('Error encoding model ['.get_class($model).'] with ID ['.$model->getKey().'] to JSON: '.$message);
    }
}
