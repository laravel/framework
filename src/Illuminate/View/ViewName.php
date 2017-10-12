<?php

namespace Illuminate\View;

class ViewName
{
    /**
     * View name aliases.
     *
     * @var array
     */
    protected static $aliases = [];

    /**
     * Normalize the given event name.
     *
     * @param  string  $name
     * @return string
     */
    public static function normalize($name)
    {
        $name = static::resolveAlias($name);

        $delimiter = ViewFinderInterface::HINT_PATH_DELIMITER;

        if (strpos($name, $delimiter) === false) {
            return str_replace('/', '.', $name);
        }

        list($namespace, $name) = explode($delimiter, $name);

        return $namespace.$delimiter.str_replace('/', '.', $name);
    }

    /**
     * Add a view name alias.
     *
     * @param $aliases array
     *
     * @return array
     */
    public static function addAliases($aliases)
    {
        static::$aliases = array_merge(static::$aliases, $aliases);
    }

    /**
     * Resolve view name by alias.
     *
     * @param $name
     *
     * @return mixed
     */
    protected static function resolveAlias($name)
    {
        return static::$aliases[$name] ?? $name;
    }
}
