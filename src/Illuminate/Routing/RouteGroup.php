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

        if (isset($new['controller'])) {
            unset($old['controller']);
        }

        $new = array_merge(static::formatAs($new, $old), [
            'metadata' => static::formatMetadata($new, $old),
            'namespace' => static::formatNamespace($new, $old),
            'prefix' => static::formatPrefix($new, $old, $prependExistingPrefix),
            'where' => static::formatWhere($new, $old),
        ]);

        return array_merge_recursive(Arr::except(
            $old, ['metadata', 'namespace', 'prefix', 'where', 'as']
        ), $new);
    }

    /**
     * Merge route metadata arrays.
     *
     * @param  array  ...$metadata
     * @return array
     */
    public static function mergeMetadata(...$metadata)
    {
        $merged = [];

        foreach ($metadata as $metadata) {
            foreach ($metadata as $key => $value) {
                if (isset($merged[$key]) && static::mergesMetadata($merged[$key], $value)) {
                    $value = static::mergeMetadata($merged[$key], $value);
                }

                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Determine if the given metadata values should be merged.
     *
     * @param  mixed  $old
     * @param  mixed  $new
     * @return bool
     */
    protected static function mergesMetadata($old, $new)
    {
        return is_array($old) &&
            is_array($new) &&
            Arr::isAssoc($old) &&
            Arr::isAssoc($new);
    }

    /**
     * Format the metadata for the new group attributes.
     *
     * @param  array  $new
     * @param  array  $old
     * @return array
     */
    protected static function formatMetadata($new, $old)
    {
        return static::mergeMetadata(
            $old['metadata'] ?? [],
            $new['metadata'] ?? []
        );
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
        }

        return isset($new['prefix']) ? trim($new['prefix'], '/').'/'.trim($old, '/') : $old;
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
