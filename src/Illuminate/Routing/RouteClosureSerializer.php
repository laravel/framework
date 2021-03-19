<?php

namespace Illuminate\Routing;

use Illuminate\Support\Str;

class RouteClosureSerializer
{
    /**
     * Determine if the given value is a serialized Closure.
     *
     * @param  string  $value
     * @return bool
     */
    public static function isSerializedClosure($value)
    {
        return Str::startsWith($value, 'C:32:"Opis\\Closure\\SerializableClosure') !== false;
    }
}
