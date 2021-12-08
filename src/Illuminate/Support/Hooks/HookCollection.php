<?php

namespace Illuminate\Support\Hooks;

use Closure;
use Illuminate\Contracts\Support\Hook;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

class HookCollection extends Collection
{
    /**
     * Cache of hooks that have already been loaded.
     *
     * @var static[]
     */
    protected static array $cache = [];

    /**
     * Get a collection of hooks for a class or an object.
     *
     * @param  object|string  $class
     * @return static
     */
    public static function for($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        return static::$cache[$class] ??= new static(static::loadHooks($class));
    }

    /**
     * Clear the hook cache.
     *
     * @return void
     */
    public static function clearCache()
    {
        static::$cache = [];
    }

    /**
     * Load the hooks for a class.
     *
     * @param  string  $class
     * @return \Illuminate\Support\Collection
     *
     * @throws \ReflectionException
     */
    protected static function loadHooks($class)
    {
        return collect((new ReflectionClass($class))->getMethods())
            ->map(fn ($method) => static::hookForMethod($method))
            ->filter();
    }

    /**
     * Load a hook for a given method based on its return type.
     *
     * @param  \ReflectionMethod  $method
     * @return \Illuminate\Support\Hooks\PendingHook|null
     */
    protected static function hookForMethod(ReflectionMethod $method): ?PendingHook
    {
        if (static::methodReturnsHook($method)) {
            return new PendingHook(function ($instance = null) use ($method) {
                return $method->invoke($instance);
            }, $method->isStatic());
        }

        return null;
    }

    /**
     * Determine if a class method returns a Hook instance.
     *
     * @param  \ReflectionMethod  $method
     * @return bool
     */
    protected static function methodReturnsHook(ReflectionMethod $method): bool
    {
        return $method->getReturnType() instanceof ReflectionNamedType
            && is_a($method->getReturnType()->getName(), Hook::class, true);
    }

    /**
     * Run all the hooks that match the given name and instance.
     *
     * @param  string  $name
     * @param  object|string  $instance
     * @param  array  $arguments
     * @param  \Closure|null  $callback
     * @return mixed
     */
    public function run($name, $instance, $arguments = [], Closure $callback = null)
    {
        $arguments = Arr::wrap($arguments);

        $hooks = $this->onlyStatic(! is_object($instance))
            ->resolve($instance)
            ->filter(fn(Hook $hook) => $hook->getName() === $name)
            ->sortBy(fn(Hook $hook) => $hook->getPriority());

        $hooks->each(fn (Hook $hook) => $hook->run($instance, $arguments));

        try {
            return $callback ? $callback() : null;
        } finally {
            $hooks->reverse()->each(fn (Hook $hook) => $hook->cleanup($instance, $arguments));
        }
    }

    /**
     * Filter collection to only static hooks.
     *
     * @param  bool  $onlyStatic
     * @return $this
     */
    public function onlyStatic($onlyStatic = true): self
    {
        if ($onlyStatic) {
            return $this->where('isStatic');
        }

        return $this;
    }

    /**
     * Map collection to resolved Hook instances.
     *
     * @param  object|string  $instance
     * @return $this
     */
    public function resolve($instance): self
    {
        return $this->map(function ($hook) use ($instance) {
            return $hook instanceof PendingHook
                ? $hook->resolve($instance)
                : $hook;
        });
    }
}
