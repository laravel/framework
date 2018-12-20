<?php

namespace Illuminate\Container;

use Closure;
use ReflectionMethod;
use ReflectionFunction;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class BoundMethod
{
    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  callable|string  $callback
     * @param  array  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     */
    public static function call($container, $callback, array $parameters = [], $defaultMethod = null)
    {
        if (static::isCallableWithAtSign($callback) || $defaultMethod) {
            return static::callClass($container, $callback, $parameters, $defaultMethod);
        }

        return static::callBoundMethod($container, $callback, function () use ($container, $callback, $parameters) {
            return call_user_func_array(
                $callback, static::getMethodDependencies($container, $callback, $parameters)
            );
        });
    }

    /**
     * Call a string reference to a class using Class@method syntax.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  string  $target
     * @param  array  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected static function callClass($container, $target, array $parameters = [], $defaultMethod = null)
    {
        $segments = explode('@', $target);

        // We will assume an @ sign is used to delimit the class name from the method
        // name. We will split on this @ sign and then build a callable array that
        // we can pass right back into the "call" method for dependency binding.
        $method = count($segments) === 2
                        ? $segments[1] : $defaultMethod;

        if (is_null($method)) {
            throw new InvalidArgumentException('Method not provided.');
        }

        return static::call(
            $container, [$container->make($segments[0]), $method], $parameters
        );
    }

    /**
     * Call a method that has been bound to the container.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  callable  $callback
     * @param  mixed  $default
     * @return mixed
     */
    protected static function callBoundMethod($container, $callback, $default)
    {
        if (! is_array($callback)) {
            return $default instanceof Closure ? $default() : $default;
        }

        // Here we need to turn the array callable into a Class@method string we can use to
        // examine the container and see if there are any method bindings for this given
        // method. If there are, we can call this method binding callback immediately.
        $method = static::normalizeMethod($callback);

        if ($container->hasMethodBinding($method)) {
            return $container->callMethodBinding($method, $callback[0]);
        }

        return $default instanceof Closure ? $default() : $default;
    }

    /**
     * Normalize the given callback into a Class@method string.
     *
     * @param  callable  $callback
     * @return string
     */
    protected static function normalizeMethod($callback)
    {
        $class = is_string($callback[0]) ? $callback[0] : get_class($callback[0]);

        return "{$class}@{$callback[1]}";
    }

    /**
     * Get all dependencies for a given method.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  callable|string  $callback
     * @param  array $inputData
     * @return array
     * @throws \ReflectionException
     */
    protected static function getMethodDependencies($container, $callback, array $inputData = []) : array
    {
        $signature = static::getCallReflector($callback)->getParameters();

        // In case the method has no explicit input parameters defined
        // we will call it, with whatever input data available to us.
        if (count($signature) === 0) {
            return $inputData;
        }

        if (! Arr::isAssoc($inputData) && (count($signature) <= count($inputData))) {
            return $inputData;
        }

        return static::addDependencyForCallParameter($container, $signature, $inputData);
    }

    /**
     * Get the proper reflection instance for the given callback.
     *
     * @param  callable|string $callback
     * @return \ReflectionFunctionAbstract
     *
     * @throws \ReflectionException
     */
    protected static function getCallReflector($callback): \Reflector
    {
        if (is_string($callback) && strpos($callback, '::') !== false) {
            $callback = explode('::', $callback);
        }

        return is_array($callback)
                        ? new ReflectionMethod($callback[0], $callback[1])
                        : new ReflectionFunction($callback);
    }

    /**
     * Add the dependencies to the input data.
     *
     * @param  \Illuminate\Container\Container $container
     * @param  array $signature
     * @param  array $inputData
     * @return array
     */
    protected static function addDependencyForCallParameter($container, array $signature, array $inputData): array
    {
        $resolvedInputData = [];
        $i = 0;

        foreach ($signature as $parameter) {
            if (array_key_exists($parameter->name, $inputData)) {
                $resolvedInputData[] = $inputData[$parameter->name];
            } elseif ($parameter->getClass() && array_key_exists($parameter->getClass()->name, $inputData)) {
                $resolvedInputData[] = $inputData[$parameter->getClass()->name];
            } elseif ($parameter->getClass()) {
                $resolvedInputData[] = $container->make($parameter->getClass()->name);
            } elseif (isset($inputData[$i])) {
                $resolvedInputData[] = $inputData[$i++];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $resolvedInputData[] = $parameter->getDefaultValue();
            }
        }

        return $resolvedInputData;
    }

    /**
     * Determine if the given string is in Class@method syntax.
     *
     * @param  mixed  $callback
     * @return bool
     */
    protected static function isCallableWithAtSign($callback): bool
    {
        return is_string($callback) && strpos($callback, '@') !== false;
    }
}
