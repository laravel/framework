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
     * @return void
     */
    public static function macro($name, $macro)
    {
        static::$macros[$name] = $macro;
    }

    /**
     * Mix another object into the class.
     *
     * @param  class-string|object  $mixin
     * @param  bool  $replace
     * @return void
     *
     * @throws \ReflectionException
     */
    public static function mixin($mixin, $replace = true)
    {
        is_string($mixin) && trait_exists($mixin)
            ? static::registerTraitMixin($mixin, $replace)
            : static::registerClassMixin($mixin, $replace);
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

    /**
     * Register trait mixins.
     *
     * @param  class-string  $mixin
     *
     * @throws \ReflectionException
     */
    protected static function registerTraitMixin(string $mixin, bool $replace): void
    {
        $object = static::resolveTraitObject($mixin);
        $methods = static::getTraitMethods($object);

        foreach ($methods as $method) {
            if (! $replace && static::hasMacro($method->name)) {
                continue;
            }

            static::macro($method->name, function (...$parameters) use ($object, $method) {
                $closure = Closure::fromCallable([$object, $method->name]);

                return $closure(...$parameters);
            });
        }
    }

    /**
     * Register class mixins.
     *
     * @param  class-string|object  $mixin
     *
     * @throws \ReflectionException
     */
    public static function registerClassMixin(string|object $mixin, bool $replace): void
    {
        $object = is_string($mixin) ? new $mixin : $mixin;
        $methods = static::getClassMethods($object);

        foreach ($methods as $method) {
            if ($replace || ! static::hasMacro($method->name)) {
                static::macro($method->name, $method->invoke($object));
            }
        }
    }

    /**
     * Resolves an anonymous class for a trait.
     *
     * @param  class-string  $mixin
     */
    protected static function resolveTraitObject(string $mixin): object
    {
        $anonymousClass = get_class(
            eval('return new class() extends '.static::class." {use {$mixin};};"),
        );

        return new $anonymousClass;
    }

    /**
     * Get the methods from the trait anonymous class.
     *
     * @return ReflectionMethod[]
     *
     * @throws \ReflectionException
     */
    protected static function getTraitMethods(object $mixin): array
    {
        $class = get_class($mixin);

        return collect(static::getClassMethods($mixin))
            ->filter(fn ($method) => $method->class === $class)
            ->all();
    }

    /**
     * Get the methods from class.
     *
     * @return ReflectionMethod[]
     *
     * @throws \ReflectionException
     */
    protected static function getClassMethods(object $mixin): array
    {
        return (new ReflectionClass($mixin))->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );
    }
}
