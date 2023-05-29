<?php

namespace Illuminate\Support;

use InvalidArgumentException;

class ItemNotNumericException extends InvalidArgumentException
{
    public static function forIncrement(string $key)
    {
        return new static("Cannot increment non-numeric collection item: {$key}!");
    }

    public static function forDecrement(string $key)
    {
        return new static("Cannot decrement non-numeric collection item: {$key}!");
    }
}
