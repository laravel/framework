<?php

namespace Illuminate\Support\Traits;

trait ReflectiveConstants
{
    /**
     * Access a constant of the class using the trait.
     *
     * @param  string  $name  The name of the constant to access.
     * @return mixed The value of the constant.
     *
     * @throws \ReflectionException If the constant does not exist or is not accessible.
     */
    public static function constant(string $name)
    {
        $class = static::class;

        if (!defined("$class::$name")) {
            throw new \ReflectionException("Constant $class::$name does not exist.");
        }

        return constant("$class::$name");
    }

    /**
     * Access a constant of the class using the trait or return null if not exists.
     *
     * @param  string  $name  The name of the constant to access.
     * @return mixed|null The value of the constant or null if it does not exist.
     */
    public static function constantOrNull(string $name)
    {
        $class = static::class;

        return defined("$class::$name") ? constant("$class::$name") : null;
    }

    /**
     * Access a constant of the class using the trait or throw an exception if not exists.
     *
     * @param  string  $name  The name of the constant to access.
     * @return mixed The value of the constant.
     *
     * @throws \ReflectionException If the constant does not exist or is not accessible.
     */
    public static function constantOrFail(string $name)
    {
        $class = static::class;

        if (!defined("$class::$name")) {
            throw new \ReflectionException("Constant $class::$name does not exist.");
        }

        return constant("$class::$name");
    }

    /**
     * Access a constant of the class using the trait or return a default value if not exists.
     *
     * @param  string  $name  The name of the constant to access.
     * @param  mixed  $default  The default value to return if the constant does not exist.
     * @return mixed The value of the constant or the default value.
     */
    public static function constantOr(string $name, $default = null)
    {
        $class = static::class;

        return defined("$class::$name") ? constant("$class::$name") : $default;
    }

    /**
     * Get all constants of the class using the trait.
     *
     * @return array Associative array with constant names as keys and their values.
     */
    public static function getAllConstants()
    {
        $class = static::class;
        $reflection = new \ReflectionClass($class);

        return $reflection->getConstants();
    }

    /**
     * Check if a constant exists in the class using the trait.
     *
     * @param  string  $name  The name of the constant to check.
     * @return bool True if the constant exists, false otherwise.
     */
    public static function hasConstant(string $name)
    {
        $class = static::class;

        return defined("$class::$name");
    }
}
