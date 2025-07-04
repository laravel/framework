<?php

namespace Illuminate\Support\Traits;

use BadMethodCallException;
use Closure;
use ReflectionClass;
use ReflectionMethod;

trait Macroable
{
    /**
     * The registered string macros.
     *
     * @var array
     */
    protected static $macros = [];

    /**
     * The registered scoped macros.
     *
     * @var array
     */
    protected static $scopedMacros = [];

    /**
     * Register a custom macro.
     *
     * @param  string  $name
     * @param  object|callable  $macro
     *
     * @param-closure-this static  $macro
     *
     * @return void
     */
    public static function macro($name, $macro)
    {
        static::$macros[$name] = $macro;
    }

    /**
     * Register a custom scoped macro, scoped macros are only
     * available to the class in which they are registered.
     *
     * @param  string  $name
     * @param  object|callable  $macro
     * @return void
     */
    public static function scopedMacro($name, $macro)
    {
        static::$scopedMacros[static::class][$name] = $macro;
    }

    /**
     * Mix another object into the class.
     *
     * @param  object  $mixin
     * @param  bool  $replace
     * @return void
     *
     * @throws \ReflectionException
     */
    public static function mixin($mixin, $replace = true)
    {
        $methods = (new ReflectionClass($mixin))->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        foreach ($methods as $method) {
            if ($replace || ! static::hasMacro($method->name)) {
                static::macro($method->name, $method->invoke($mixin));
            }
        }
    }

    /**
     * Checks if macro is registered.
     *
     * @param  string  $name
     * @return bool
     */
    public static function hasMacro($name)
    {
        return static::hasGlobalMacro($name) || static::hasScopedMacro($name);
    }

    /**
     * Checks if scoped macro is registered.
     *
     * @param  string  $name
     * @return bool
     */
    public static function hasScopedMacro($name): bool
    {
        return isset(static::$scopedMacros[static::class][$name]);
    }

    public static function hasGlobalMacro($name): bool
    {
        return isset(static::$macros[$name]);
    }

    /**
     * Flush the existing macros.
     *
     * @return void
     */
    public static function flushMacros()
    {
        static::$macros = [];
        static::$scopedMacros = [];
    }

    /**
     * Dynamically handle static method calls to the class.
     * Scoped macros take priority over global macros.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic($method, $parameters)
    {
        if (! static::hasMacro($method)) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        if (static::hasScopedMacro($method)) {
            $macro = static::$scopedMacros[static::class][$method];
        } else {
            $macro = static::$macros[$method];
        }

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo(null, static::class);
        }

        return $macro(...$parameters);
    }

    /**
     * Dynamically handle method calls to the class instance.
     * Scoped macros take priority over global macros.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (! static::hasMacro($method)) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        if (static::hasScopedMacro($method)) {
            $macro = static::$scopedMacros[static::class][$method];
        } else {
            $macro = static::$macros[$method];
        }

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo($this, static::class);
        }

        return $macro(...$parameters);
    }
}
