<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class InvalidEnumValueException extends RuntimeException
{
    public static function make($model, $key, $value)
    {
        $class = get_class($model);
        
        return new static("Invalid enum value: [{$value}] for enum [{$key}] on model [{$class}]");
    }
}
