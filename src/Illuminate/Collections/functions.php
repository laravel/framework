<?php

namespace Illuminate\Support;

if (! function_exists('Illuminate\Support\enum_value')) {
    /**
     * Return a scalar value for the given value that might be an enum.
     *
     * @internal
     *
     * @param  mixed  $value
     * @return mixed
     */
    function enum_value($value)
    {
        return match (true) {
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \UnitEnum => $value->name,

            default => $value,
        };
    }
}
