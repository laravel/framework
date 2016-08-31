<?php

namespace Illuminate\Validation\Rules\Traits;

use Illuminate\Support\Arr;

trait ExtractDataFromPath
{
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
    protected function getLeadingExplicitAttributePath($attribute)
    {
        return rtrim(explode('*', $attribute)[0], '.') ?: null;
    }

    /**
     * Extract data based on the given dot-notated path.
     *
     * Used to extract a sub-section of the data for faster iteration.
     *
     * @param  string  $attribute
     * @return array
     */
    protected function extractDataFromPath($attribute, $validator)
    {
        $results = [];

        $value = Arr::get($validator->getData(), $attribute, '__missing__');

        if ($value != '__missing__') {
            Arr::set($results, $attribute, $value);
        }

        return $results;
    }
}
