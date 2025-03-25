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
        static::$macros[static::class][$name] = $macro;
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
        return isset(static::$macros[static::class][$name]) || (($parent = get_parent_class(static::class)) && $parent::hasMacro($name));
    }

    /**
     * Get a registered macro.
     * 
     * @param  string  $name
     * @return object|callable|null
     */
    public static function getMacro($method)
    {
        return static::$macros[static::class][$method] ?? (($parent = get_parent_class(static::class)) ? $parent::getMacro($method) : null);
    }

    /**
     * Flush the existing macros.
     *
     * @return void
     */
    public static function flushMacros($all = true)
    {
        if ($all) {
            static::$macros = [];
        } else {
            static::$macros[static::class] = [];
        }
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

        $macro = static::getMacro($method);

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

        $macro = static::getMacro($method);

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo($this, static::class);
        }

        return $macro(...$parameters);
    }
}
