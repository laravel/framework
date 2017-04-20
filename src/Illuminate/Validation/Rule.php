<?php

namespace Illuminate\Validation;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Support\Traits\Macroable;

class Rule
{
    use Macroable {
        __callStatic as macroCallStatic;
    }

    /**
     * Get the rule class.
     *
     * @param  string $name
     * @return string
     */
    protected static function getRuleClass($name)
    {
        return 'Illuminate\Validation\Rules\\'.Str::studly($name);
    }

    /**
     * Handle magic static calls.
     *
     * @param  string $method
     * @param  mixed $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        if (self::hasMacro($method)) {
            return self::macroCallStatic($method, $args);
        }

        $class = self::getRuleClass($method);

        if (! class_exists($class)) {
            throw new InvalidArgumentException("Could not find rule '$method'");
        }

        return new $class(...$args);
    }
}
