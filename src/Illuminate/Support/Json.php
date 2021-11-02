<?php

namespace Illuminate\Support;

class Json
{
    /**
     * Compile a PHP expression into a JavaScript object, array or single-quoted string.
     *
     * @param mixed $expression
     * @param int|null $options
     * @param int $depth
     * @return string
     */
    public static function parse($expression, $options = null, $depth = 512)
    {
        if (is_object($expression) || is_array($expression)) {
            $base64 = base64_encode(json_encode($expression, $options, $depth));

            return "JSON.parse(atob('{$base64}'))";
        }

        if (is_string($expression)) {
            return str_replace('"', "'", json_encode($expression));
        }

        return json_encode($expression, $options, $depth);
    }

    /**
     * Compile the PHP statement into encoded JSON with double-quoted strings.
     *
     * @param mixed $expression
     * @param int|string $options
     * @param int $depth
     * @return string
     */
    public static function encode($expression, $options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT, $depth = 512)
    {
        return json_encode($expression, $options, $depth);
    }

    /**
     * Compile a PHP boolean into JavaScript true/false.
     *
     * @param bool $expression
     * @return string
     */
    public static function bool($expression)
    {
        return json_encode($expression);
    }

    /**
     * Compile a PHP string into JavaScript single-quoted string.
     *
     * @param string $expression
     * @return string
     */
    public static function str($expression)
    {
        return str_replace('"', "'", json_encode($expression));
    }
}
