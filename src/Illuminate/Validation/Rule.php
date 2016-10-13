<?php

namespace Illuminate\Validation;

class Rule
{
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
