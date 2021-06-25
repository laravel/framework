<?php

namespace Illuminate\Routing;

use Illuminate\Support\Arr;

class RouteGroup
{
    /**
     * Merge route groups into a new array.
     *
     * @param  array  $new
     * @param  array  $old
     * @param  bool  $prependExistingPrefix
     * @return array
     */
    public static function merge($new, $old, $prependExistingPrefix = true)
    {
        if (isset($new['domain'])) {
            unset($old['domain']);
        }

        $newAttributes = static::formatAs($new, $old);
        $newAttributes['namespace'] = static::formatNamespace($new, $old);
        $newAttributes['prefix'] = static::formatPrefix($new, $old, $prependExistingPrefix);
        $newAttributes['where'] = static::formatWhere($new, $old);

        unset($old['namespace'], $old['prefix'], $old['where'], $old['as']);

        return array_merge_recursive($old, $newAttributes);
    }

    /**
     * Format the namespace for the new group attributes.
     *
     * @param  array  $new
     * @param  array  $old
     * @return string|null
     */
    protected static function formatNamespace($new, $old)
    {
        if (isset($new['namespace'])) {
            return isset($old['namespace']) && strpos($new['namespace'], '\\') !== 0
                    ? trim($old['namespace'], '\\').'\\'.trim($new['namespace'], '\\')
                    : trim($new['namespace'], '\\');
        }

        return $old['namespace'] ?? null;
    }

    /**
     * Format the prefix for the new group attributes.
     *
     * @param  array  $new
     * @param  array  $old
     * @param  bool  $prependExistingPrefix
     * @return string|null
     */
    protected static function formatPrefix($new, $old, $prependExistingPrefix = true)
    {
        $old = $old['prefix'] ?? null;

        if ($prependExistingPrefix) {
            return isset($new['prefix']) ? trim($old, '/').'/'.trim($new['prefix'], '/') : $old;
        } else {
            return isset($new['prefix']) ? trim($new['prefix'], '/').'/'.trim($old, '/') : $old;
        }
    }

    /**
     * Format the "wheres" for the new group attributes.
     *
     * @param  array  $new
     * @param  array  $old
     * @return array
     */
    protected static function formatWhere($new, $old)
    {
        $wheres = $old['where'] ?? [];

        foreach ($new['where'] ?? [] as $key => $where) {
            $wheres[$key] = $where;
        }

        return $wheres;
    }

    /**
     * Format the "as" clause of the new group attributes.
     *
     * @param  array  $new
     * @param  array  $old
     * @return array
     */
    protected static function formatAs($new, $old)
    {
        if (isset($old['as'])) {
            $new['as'] = $old['as'].($new['as'] ?? '');
        }

        return $new;
    }
}
