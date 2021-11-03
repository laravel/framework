<?php

namespace Illuminate\Support;

class Js
{
    /**
     * Convert an expression into a valid JavaScript object, JSON string, or string.
     *
     * @param  mixed  $expression
     * @param  int|null  $options
     * @param  int  $depth
     * @return string
     */
    public static function from($expression, $options = null, $depth = 512)
    {
        if (is_object($expression) || is_array($expression)) {
            $base64 = base64_encode(json_encode($expression, $options, $depth));

            return "JSON.parse(atob('{$base64}'))";
        }

        if (is_string($expression)) {
            return "'".str_replace("'", "\'", str_replace('\\', '\\\\', $expression))."'";
        }

        return json_encode($expression, $options, $depth);
    }
}
