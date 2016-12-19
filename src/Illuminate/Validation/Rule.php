<?php

namespace Illuminate\Validation;

use Illuminate\Support\Traits\Macroable;

class Rule
{
    use Macroable;

    /**
     * Get a dimensions constraint builder instance.
     *
     * @param  array  $constraints
     * @return \Illuminate\Validation\Rules\Dimensions
     */
    public static function dimensions(array $constraints = [])
    {
        return new Rules\Dimensions($constraints);
    }

    /**
     * Get a exists constraint builder instance.
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Illuminate\Validation\Rules\Exists
     */
    public static function exists($table, $column = 'NULL')
    {
        return new Rules\Exists($table, $column);
    }

    /**
     * Get an in constraint builder instance.
     *
     * @param  array  $values
     * @param  bool  $arrayKeys
     * @return \Illuminate\Validation\Rules\In
     */
    public static function in(array $values, $arrayKeys = false)
    {
        return new Rules\In($values, $arrayKeys);
    }

    /**
     * Get a not_in constraint builder instance.
     *
     * @param  array  $values
     * @param  bool  $arrayKeys
     * @return \Illuminate\Validation\Rules\NotIn
     */
    public static function notIn(array $values, $arrayKeys = false)
    {
        return new Rules\NotIn($values, $arrayKeys);
    }

    /**
     * Get an in constraint builder instance for array keys.
     *
     * @param  array  $values
     * @return \Illuminate\Validation\Rules\In
     */
    public static function inKeys(array $values)
    {
        return new Rules\In($values, true);
    }

    /**
     * Get a not_in constraint builder instance for array keys.
     *
     * @param  array  $values
     * @return \Illuminate\Validation\Rules\NotIn
     */
    public static function notInKeys(array $values)
    {
        return new Rules\NotIn($values, true);
    }

    /**
     * Get a unique constraint builder instance.
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Illuminate\Validation\Rules\Unique
     */
    public static function unique($table, $column = 'NULL')
    {
        return new Rules\Unique($table, $column);
    }
}
