<?php

namespace Illuminate\Container;

use Closure;
use ReflectionMethod;
use ReflectionFunction;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Contracts\Container\Container as ContainerContract;

class BoundMethod
{
    /**
     * @var ContainerContract
     */
    private static $container;

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  ContainerContract  $container
     * @param  callable|string  $callback
     * @param  array  $inputData
     * @param  string|null  $defaultMethod
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function call(ContainerContract $container, $callback, array $inputData = [], $defaultMethod = null)
    {
        self::setContainer($container);

        if (static::isCallableWithAtSign($callback) || $defaultMethod) {
            return static::callClass($callback, $inputData, $defaultMethod);
        }

        return static::callBoundMethod($callback, function () use ($callback, $inputData) {
            return call_user_func_array(
                $callback, static::getMethodDependencies($callback, $inputData)
            );
        });
    }

    /**
     * Call a string reference to a class using Class@method syntax.
     *
     * @param  string  $target
     * @param  array  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     *
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected static function callClass($target, array $parameters = [], $defaultMethod = null)
    {
        $segments = explode('@', $target);

        // We will assume an @ sign is used to delimit the class name from the method
        // name. We will split on this @ sign and then build a callable array that
        // we can pass right back into the "call" method for dependency binding.
        $method = count($segments) === 2 ? $segments[1] : $defaultMethod;

        if (is_null($method)) {
            throw new InvalidArgumentException('Method not provided.');
        }

        return static::call(
            self::$container, [self::$container->make($segments[0]), $method], $parameters
        );
    }

    /**
     * Call a method that has been bound to the container.
     *
     * @param  callable  $callback
     * @param  mixed  $default
     * @return mixed
     */
    protected static function callBoundMethod($callback, $default)
    {
        if (! is_array($callback)) {
            return $default instanceof Closure ? $default() : $default;
        }

        // Here we need to turn the array callable into a Class@method string we can use to
        // examine the container and see if there are any method bindings for this given
        // method. If there are, we can call this method binding callback immediately.
        $method = static::normalizeMethod($callback);

        if (self::$container->hasMethodBinding($method)) {
            return self::$container->callMethodBinding($method, $callback[0]);
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
     * @param  callable|string  $callback
     * @param  array  $inputData
     * @return array
     *
     * @throws \ReflectionException
     */
    protected static function getMethodDependencies($callback, array $inputData = [])
    {
        $signature = static::getCallReflector($callback)->getParameters();

        // In case the method has no explicit input parameters we will
        // call that with whatever input data available to us since
        // they may have used func_get_args() to catch the input.
        if (count($signature) === 0) {
            return $inputData;
        }

        // When we receive the input as an indexed array and the count of passed arguments
        // is not less than the declared parameters, it means that we are provided with
        // everything needed, So the IOC container should not bother about injection.
        if (! Arr::isAssoc($inputData) && (count($signature) <= count($inputData))) {
            return $inputData;
        }

        return static::addDependenciesToInputData($signature, $inputData);
    }

    /**
     * Get the proper reflection instance for the given callback.
     *
     * @param  callable|string  $callback
     * @return \ReflectionFunctionAbstract
     *
     * @throws \ReflectionException
     */
    protected static function getCallReflector($callback)
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
     * @param  array  $signature
     * @param  array  $inputData
     * @return array
     */
    protected static function addDependenciesToInputData(array $signature, array $inputData)
    {
        // Here we iterate through the list of declared parameters (in the method signature) and decide
        // whether it should be invoked with the provided input data, or we should resolve an object
        // for it (according to it's type-hint) or just call it with it's defined "default" value.
        $resolvedInputData = [];
        $i = 0;

        foreach ($signature as $parameter) {
            if (array_key_exists($parameter->name, $inputData)) {
                $resolvedInputData[] = $inputData[$parameter->name];
            } elseif ($class = $parameter->getClass()) {
                $resolvedInputData[] = self::getInstance($inputData, $class->name, $i);
            } elseif (array_key_exists($i, $inputData)) {
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
     * @param  string|callable  $callback
     * @return bool
     */
    protected static function isCallableWithAtSign($callback)
    {
        return is_string($callback) && strpos($callback, '@') !== false;
    }

    /**
     * @param array $inputData
     * @param $className
     * @param $i
     * @return mixed
     */
    protected static function getInstance(array $inputData, $className, &$i)
    {
        if (array_key_exists($className, $inputData)) {
            // gets from associative array input data
            return $inputData[$className];
        } elseif (isset($inputData[$i]) && is_a($inputData[$i], $className)) {
            // gets from indexed array input data
            return $inputData[$i++];
        } else {
            // resolves a new instance
            return self::$container->make($className);
        }
    }

    /**
     * @param ContainerContract $container
     */
    public static function setContainer(ContainerContract $container)
    {
        self::$container = $container;
    }
}
