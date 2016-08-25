<?php

namespace Illuminate\Container;

use Closure;
use Reflector;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;
use Illuminate\Contracts\Container\BindingResolutionException as Exception;

class ContainerResolver
{
    /**
     * The stack of concretions currently being built.
     *
     * @var array
     */
    protected $buildStack = [];

    /**
     * Check if something is a class
     *
     * @param  mixed  $value
     * @return boolean
     */
    public static function isClass($value)
    {
        return is_string($value) && class_exists($value);
    }

    /**
     * Check if something is a method
     *
     * @param  mixed  $value
     * @return boolean
     */
    public static function isMethod($value)
    {
        return is_callable($value) && !self::isFunction($value);
    }

    /**
     * Check if something is a closure
     *
     * @param  mixed  $value
     * @return boolean
     */
    public static function isClosure($value)
    {
        return $value instanceof Closure;
    }

    /**
     * Check if something is a function
     *
     * @param  mixed  $value
     * @return boolean
     */
    public static function isFunction($value)
    {
        return is_callable($value) && ($value instanceof Closure || is_string($value) && function_exists($value));
    }

    /**
     * Check if something is resolvable
     * @param  mixed  $value
     * @return boolean
     */
    public static function isResolvable($value)
    {
        return self::isClass($value) || self::isMethod($value) || self::isFunction($value);
    }

    /**
     * Resolve a closure, function, method or a class
     * @param  string|array $subject
     * @param  array  $parameters
     * @return mixed
     */
    public function resolve($subject, array $parameters = [])
    {
        if (self::isClass($subject)) {
            return $this->resolveClass($subject, $parameters);
        } else if (self::isMethod($subject)) {
            return $this->resolveMethod($subject, $parameters);
        } else if (self::isFunction($subject)) {
            return $this->resolveFunction($subject, $parameters);
        }

        throw new Exception("[$subject] is not resolvable. Build stack : [".implode(', ', $this->buildStack)."]");
    }

    /**
     * Resolve a class
     * @param  string $subject
     * @param  array  $parameters
     * @return mixed
     */
    public function resolveClass($class, array $parameters = [])
    {
        $reflectionClass = new ReflectionClass($class);
        $reflectionMethod = $reflectionClass->getConstructor();

        array_push($this->buildStack, $reflectionClass->getName());

        if ($reflectionMethod) {
            $reflectionParameters = $reflectionMethod->getParameters();
        	$parameters = $this->resolveParameters($reflectionParameters, $parameters);
        }

        array_pop($this->buildStack);

        return $reflectionClass->newInstanceArgs($parameters);
    }

    /**
     * Resolve a method
     * @param  string|array $subject
     * @param  array  $parameters
     * @return mixed
     */
    public function resolveMethod($method, array $parameters = [])
    {
        $reflectionMethod = is_string($method) ? new ReflectionMethod($method) : new ReflectionMethod($method[0], $method[1]);
        $reflectionParameters = $reflectionMethod->getParameters();

        array_push($this->buildStack, $reflectionMethod->getName());

        $resolvedParameters = $this->resolveParameters($reflectionParameters, $parameters);

        array_pop($this->buildStack);

        return call_user_func_array($method, $resolvedParameters);
    }

    /**
     * Resolve a closure / function
     * @param  string|\Closure $subject
     * @param  array  $parameters
     * @return mixed
     */
    public function resolveFunction($function, array $parameters = [])
    {
        $reflectionFunction = new ReflectionFunction($function);
        $reflectionParameters = $reflectionFunction->getParameters();

        array_push($this->buildStack, $reflectionFunction->getName());

        $resolvedParameters = $this->resolveParameters($reflectionParameters, $parameters);

        array_pop($this->buildStack);

        return $reflectionFunction->invokeArgs($resolvedParameters);
    }

    /**
     * Resolve a parameter
     * @param  \ReflectionParameter $parameter
     * @param  array               $parameters
     * @return mixed
     */
    protected function resolveParameter(ReflectionParameter $parameter, array $parameters = [])
    {
        $name = $parameter->getName();
        $index = $parameter->getPosition();

        if (isset($parameters[$name])) {
            return $parameters[$parameter->name];
        }
        if (isset($parameters[$index])) {
            return $parameters[$index];
        }
        if (($class = $parameter->getClass())) {
            return $this->resolve($class->name);
        }
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new Exception("Unresolvable dependency resolving [$parameter] in [".end($this->buildStack)."]");
    }

    /**
     * Resolve an array of \ReflectionParameter parameters
     * @param  array  $reflectionParameters
     * @param  array  $parameters
     * @return array
     */
    protected function resolveParameters(array $reflectionParameters, array $parameters = [])
    {
        $dependencies = [];

        foreach ($reflectionParameters as $key => $parameter) {
            $dependencies[] = $this->resolveParameter($parameter, $parameters);
        }

        return self::mergeParameters($dependencies, $parameters);
    }

    /**
     * Merge some dynamicly resolved parameters whith some others provided by the user
     * @param  array  $rootParameters
     * @param  array  $parameters
     * @return array
     */
    private static function mergeParameters(array $rootParameters, array $parameters = [])
    {
        foreach ($parameters as $key => $value) {
            if (!isset($rootParameters[$key]) && is_numeric($key)) {
                $rootParameters[$key] = $value;
            }
        }

        return $rootParameters;
    }
}
