<?php

namespace Illuminate\Support\Traits;

use BadMethodCallException;
use Closure;
use Illuminate\Macroable\Exceptions\MacroAlreadyDefinedException;
use Illuminate\Support\MacroOverrides;
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
     * Indicates if a macros should not be overridden when it already exists.
     *
     * @var bool
     */
    protected static $preventMacroOverrides = false;

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
        if (static::shouldPreventMacroOverrides() && static::hasMacro($name)) {
            throw new MacroAlreadyDefinedException(sprintf(
                'Macro %s::%s already exists.', static::class, $name
            ));
        }

        static::$macros[$name] = $macro;
    }

    /**
     * Prevents macro overrides.
     *
     * @param  bool  $prevent
     * @return void
     */
    public static function preventMacroOverrides($prevent = true): void
    {
        static::$preventMacroOverrides = $prevent;
    }

    /**
     * Determines if macro overrides should be prevented.
     *
     * @return bool
     */
    public static function shouldPreventMacroOverrides(): bool
    {
        return static::$preventMacroOverrides === null ? MacroOverrides::shouldPrevent() : static::$preventMacroOverrides;
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
    }

    /**
     * Dynamically handle calls to the class.
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

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo(null, static::class);
        }

        return $macro(...$parameters);
    }

    /**
     * Dynamically handle calls to the class.
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

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo($this, static::class);
        }

        return $macro(...$parameters);
    }
}
