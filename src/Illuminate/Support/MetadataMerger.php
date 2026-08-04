<?php

namespace Illuminate\Support;

/**
 * @internal
 */
class MetadataMerger
{
    /**
     * Merge the given metadata.
     *
     * Associative array values are merged recursively, while all other values, including lists, replace the existing value entirely.
     *
     * @param  array  $existing
     * @param  array  $incoming
     * @return array
     */
    public static function merge(array $existing, array $incoming): array
    {
        foreach ($incoming as $key => $value) {
            if (isset($existing[$key]) && static::mergeable($existing[$key], $value)) {
                $value = static::merge($existing[$key], $value);
            }

            $existing[$key] = $value;
        }

        return $existing;
    }

    /**
     * Determine if the given metadata values should be merged.
     *
     * @param  mixed  $existing
     * @param  mixed  $incoming
     * @return bool
     */
    protected static function mergeable($existing, $incoming)
    {
        return is_array($existing) &&
            is_array($incoming) &&
            Arr::isAssoc($existing) &&
            Arr::isAssoc($incoming);
    }
}
