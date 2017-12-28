<?php

namespace Illuminate\Support\Pluralizers;

interface PluralizerInterface
{
    /**
     * Get the plural form of a word.
     *
     * @param  string  $value
     * @param  int     $count
     * @return string
     */
    public static function plural($value, $count);

    /**
     * Get the singular form of a word.
     *
     * @param  string  $value
     * @return string
     */
    public static function singular($value);
}