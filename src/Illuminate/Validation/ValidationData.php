<?php

namespace Illuminate\Validation;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

class ValidationData
{
    /**
     * Initialize and gather data for the given attribute.
     *
     * @param  string  $attribute
     * @param  array  $masterData
     * @return array
     */
    public static function initializeAndGatherData($attribute, $masterData)
    {
        $data = Arr::dot(static::initializeAttributeOnData($attribute, $masterData));

        return array_merge($data, static::extractValuesForWildcards(
            $masterData, $data, $attribute
        ));
    }

    /**
     * Gather a copy of the attribute data filled with any missing attributes.
     *
     * @param  string  $attribute
     * @param  array  $masterData
     * @return array
     */
    protected static function initializeAttributeOnData($attribute, $masterData)
    {
        $explicitPath = static::getLeadingExplicitAttributePath($attribute);

        $data = static::initializeAndArrayify(
            static::extractDataFromPath($explicitPath, $masterData)
        );

        if (! str_contains($attribute, '*') || str_ends_with($attribute, '*')) {
            return $data;
        }

        return data_set($data, $attribute, null, true);
    }

    /**
     * Recursively convert the given data into a plain array.
     *
     * @param  mixed  $data
     * @return mixed
     */
    public static function initializeAndArrayify($data)
    {
        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }

        if (! is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (is_array($value) || $value instanceof Arrayable || $value instanceof \Traversable) {
                $data[$key] = static::initializeAndArrayify($value);
            }
        }

        return $data;
    }

    /**
     * Get all of the exact attribute values for a given wildcard attribute.
     *
     * @param  array  $masterData
     * @param  array  $data
     * @param  string  $attribute
     * @return array
     */
    protected static function extractValuesForWildcards($masterData, $data, $attribute)
    {
        $keys = [];

        $pattern = str_replace('\*', '[^\.]+', preg_quote($attribute, '/'));

        foreach ($data as $key => $value) {
            if ((bool) preg_match('/^'.$pattern.'/', $key, $matches)) {
                $keys[] = $matches[0];
            }
        }

        $keys = array_unique($keys);

        $data = [];

        foreach ($keys as $key) {
            $data[$key] = Arr::get($masterData, $key);
        }

        return $data;
    }

    /**
     * Extract data based on the given dot-notated path.
     *
     * Used to extract a sub-section of the data for faster iteration.
     *
     * @param  string  $attribute
     * @param  array  $masterData
     * @return array
     */
    public static function extractDataFromPath($attribute, $masterData)
    {
        $results = [];

        $value = Arr::get($masterData, $attribute, '__missing__');

        if ($value !== '__missing__') {
            Arr::set($results, $attribute, $value);
        }

        return $results;
    }

    /**
     * Get the explicit part of the attribute name.
     *
     * E.g. 'foo.bar.*.baz' -> 'foo.bar'
     *
     * Allows us to not spin through all of the flattened data for some operations.
     *
     * @param  string  $attribute
     * @return string
     */
    public static function getLeadingExplicitAttributePath($attribute)
    {
        return rtrim(explode('*', $attribute)[0], '.') ?: null;
    }
}
