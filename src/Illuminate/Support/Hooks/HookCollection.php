<?php

namespace Illuminate\Support\Hooks;

use Closure;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

class HookCollection extends Collection
{
    protected static array $cache = [];

    protected static array $registrars = [];

    public static function for($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        return static::$cache[$class] ??= new static(static::loadHooks($class));
    }

    public static function clearCache()
    {
        static::$cache = [];
    }

    //public static function register($className, Closure $callback)
    //{
    //    static::$registrars[$className][] = $callback;
    //}
    //
    //public static function registerTraitPrefix($className, $prefix)
    //{
    //    static::register($className, function($hooks, $class) use ($prefix) {
    //        foreach (class_uses_recursive($class) as $trait) {
    //            $method = $prefix.class_basename($trait);
    //
    //            if (method_exists($class, $method)) {
    //                $hooks->push(new Hook($prefix, Closure::fromCallable([$class, $method])));
    //            }
    //        }
    //    });
    //}

    protected static function loadHooks($class)
    {
        $classNames = array_values(array_merge(
            [$class], class_parents($class), class_implements($class)
        ));

        return collect((new ReflectionClass($class))->getMethods())
            ->map(fn($method) => static::hookForMethod($method, $classNames))
            ->filter();
    }

    protected static function hookForMethod(ReflectionMethod $method, array $classNames): ?PendingHook
    {
        if (static::methodReturnsHook($method)) {
            return new PendingHook(static function($instance = null) use ($method) {
                return $method->invoke($instance);
            }, $method->isStatic());
        }

        // FIXME: Allow for registered hooks by name

        return null;
    }

    protected static function methodReturnsHook(ReflectionMethod $method): bool
    {
        return $method->getReturnType() instanceof ReflectionNamedType
            && $method->getReturnType()->getName() === Hook::class;
    }

    public function run($name, $instance = null, $arguments = [])
    {
        $hooks = $this->where('isStatic', is_null($instance))
            ->map(fn(PendingHook $pending) => $pending->resolve($instance));

        $hooks->where('name', $name)
            ->sortBy('priority')
            ->each(fn(Hook $hook) => $hook->run($instance, $arguments));
    }
}
