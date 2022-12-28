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
     * @param  bool  $appendExistingSuffix
     * @return array
     */
    public static function merge($new, $old, $prependExistingPrefix = true, $appendExistingSuffix = true)
    {
        if (isset($new['domain'])) {
            unset($old['domain']);
        }

        if (isset($new['controller'])) {
            unset($old['controller']);
        }

        $new = array_merge(static::formatAs($new, $old), [
            'namespace' => static::formatNamespace($new, $old),
            'prefix' => static::formatPrefix($new, $old, $prependExistingPrefix),
            'suffix' => static::formatSuffix($new, $old, $appendExistingSuffix),
            'where' => static::formatWhere($new, $old),
        ]);

        return array_merge_recursive(Arr::except(
            $old, ['namespace', 'prefix', 'suffix', 'where', 'as']
        ), $new);
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
            return isset($old['namespace']) && ! str_starts_with($new['namespace'], '\\')
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
        $old = $old['prefix'] ?? '';

        if ($prependExistingPrefix) {
            return isset($new['prefix']) ? trim($old, '/').'/'.trim($new['prefix'], '/') : $old;
        } else {
            return isset($new['prefix']) ? trim($new['prefix'], '/').'/'.trim($old, '/') : $old;
        }
    }

    /**
     * Format the suffix for the new group attributes.
     *
     * @param  array  $new
     * @param  array  $old
     * @param  bool  $appendExistingSuffix
     * @return string|null
     */
    protected static function formatSuffix($new, $old, $appendExistingSuffix = true)
    {
        $old = $old['suffix'] ?? '';

        if ($appendExistingSuffix) {
            return isset($new['suffix']) ? trim($new['suffix'], '/').'/'.trim($old, '/') : $old;
        } else {
            return isset($new['suffix']) ? trim($old, '/').'/'.trim($new['suffix'], '/'): $old;
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
        return array_merge(
            $old['where'] ?? [],
            $new['where'] ?? []
        );
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
