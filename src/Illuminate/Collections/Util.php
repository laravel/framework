<?php

namespace Illuminate\Support;

use Closure;

/**
 * @internal
 */
class Util
{
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public static function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}
