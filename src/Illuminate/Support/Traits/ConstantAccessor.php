<?php

namespace Illuminate\Support;

trait ConstantAccessor
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
        $class = get_called_class();

        if (! defined("$class::$name")) {
            throw new \ReflectionException("Constant $class::$name does not exist.");
        }

        return constant("$class::$name");
    }
}