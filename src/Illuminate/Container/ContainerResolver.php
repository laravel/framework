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

    public static function isClass($value)
    {
        return is_string($value) && class_exists($value);
    }

    public static function isMethod($value)
    {
        return is_callable($value) && !self::isFunction($value);
    }

    public static function isFunction($value)
    {
        return is_callable($value) && ($value instanceof Closure || is_string($value) && function_exists($value));
    }

    /**
     * Resolve a closure / function / method / class
     * @param  string|array $subject
     * @param  array  $parameters
     * @return mixed
     */
    public function resolve($subject, array $parameters = [])
    {
        if (self::isClass($subject)) {
            $resolved = $this->resolveClass($subject, $parameters);
        } else if (self::isMethod($subject)) {
            $resolved = $this->resolveMethod($subject, $parameters);
        } else if (self::isFunction($subject)) {
            $resolved = $this->resolveFunction($subject, $parameters);
        } else {
            throw new Exception("[$subject] is not resolvable. Build stack : [".implode(', ', $this->buildStack)."]");
        }

        array_pop($this->buildStack);

        return $resolved;
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
        $this->buildStack[] = $reflectionClass->getName();

        if (($reflectionMethod = $reflectionClass->getConstructor())) {
        	$reflectionParameters = $reflectionMethod->getParameters();

        	$resolvedParameters = $this->resolveParameters($reflectionParameters, $parameters);

            return $reflectionClass->newInstanceArgs($resolvedParameters);
        }

        return $reflectionClass->newInstanceArgs();
    }

    /**
     * Resolve a method
     * @param  string|array $subject
     * @param  array  $parameters
     * @return mixed
     */
    public function resolveMethod($method, array $parameters = [])
    {
        if (is_string($method)) {
            $reflectionMethod = new ReflectionMethod($method);
        } else {
            $reflectionMethod = new ReflectionMethod($method[0], $method[1]);
        }

        $reflectionParameters = $reflectionMethod->getParameters();
        $this->buildStack[] = $reflectionMethod->getName();

        $resolvedParameters = $this->resolveParameters($reflectionParameters, $parameters);

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
        $this->buildStack[] = $reflectionFunction->getName();

        $resolvedParameters = $this->resolveParameters($reflectionParameters, $parameters);

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
        if ($parameter->getClass()) {
            return $this->resolve($parameter->getClass()->name);
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
            if (is_numeric($key) && !isset($rootParameters[$key])) {
                $rootParameters[$key] = $value;
            }
        }

        return $rootParameters;
    }
}
