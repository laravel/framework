<?php

namespace Illuminate\Support;

use Doctrine\Common\Inflector\Inflector;

class Pluralizer
{
    /**
     * Get the plural form of an English word.
     *
     * @param  string  $value
     * @param  int     $count
     * @return string
     */
    public static function plural($value, $count = 2)
    {
        if ((int) $count === 1) {
            return $value;
        }

        return static::matchCase(Inflector::pluralize($value), $value);
    }

    /**
     * Get the singular form of an English word.
     *
     * @param  string  $value
     * @return string
     */
    public static function singular($value)
    {
        $singular = Inflector::singularize($value);

        return static::matchCase($singular, $value);
    }

    /**
     * Attempt to match the case on two strings.
     *
     * @param  string  $value
     * @param  string  $comparison
     * @return string
     */
    protected static function matchCase($value, $comparison)
    {
        $functions = ['mb_strtolower', 'mb_strtoupper', 'ucfirst', 'ucwords'];

        foreach ($functions as $function) {
            if (call_user_func($function, $comparison) === $comparison) {
                return call_user_func($function, $value);
            }
        }

        return $value;
    }
}
