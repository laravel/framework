<?php

namespace Illuminate\Support\Traits;

use Closure;
use BadMethodCallException;
use Illuminate\Contracts\Support\Macro;

trait Macroable
{
    /**
     * The registered string macros.
     *
     * @var array
     */
    protected static $macros = [];

    /**
     * Register a custom macro.
     *
     * @param  string $name
     * @param  \Illuminate\Contracts\Support\Macro|callable $macro
     *
     * @return void
     */
    public static function macro($name, $macro)
    {
        static::$macros[$name] = $macro;
    }

    /**
     * Checks if macro is registered.
     *
     * @param  string  $name
     * @return bool
     */
    public static function hasMacro($name)
    {
        return isset(static::$macros[$name]);
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic($method, $parameters)
    {
        if (! static::hasMacro($method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        if (static::$macros[$method] instanceof Closure) {
            return call_user_func_array(Closure::bind(static::$macros[$method], null, static::class), $parameters);
        }

        return call_user_func_array(static::$macros[$method], $parameters);
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (! static::hasMacro($method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        $macro = static::$macros[$method];

        if (is_string($macro) && is_a($macro, Macro::class, true)) {
            $macro = (new $macro)->handle();
        }

        if ($macro instanceof Closure) {
            return call_user_func_array($macro->bindTo($this, static::class), $parameters);
        }

        return call_user_func_array($macro, $parameters);
    }
}
