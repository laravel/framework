<?php

namespace Illuminate\View;

class ViewName
{
    private static array $cache = [];

    public static function normalize($name)
    {
        return static::$cache[$name] ??= static::_normalize($name);
    }

    /**
     * Normalize the given view name.
     *
     * @param  string  $name
     * @return string
     */
    public static function _normalize($name)
    {
        $delimiter = ViewFinderInterface::HINT_PATH_DELIMITER;

        if (! str_contains($name, $delimiter)) {
            return str_replace('/', '.', $name);
        }

        [$namespace, $name] = explode($delimiter, $name);

        return $namespace.$delimiter.str_replace('/', '.', $name);
    }
}
