<?php

namespace Illuminate\Container;

use Closure;
use Reflector;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;

class ContainerResolver
{
    protected $buildStack = [];

    public function resolve($subject, array $parameters = [])
    {
        if (is_callable($subject)) {
            if (is_string($subject) && function_exists($subject) || $subject instanceof Closure) {
                $resolved = $this->resolveFunction($subject, $parameters);
            } else {
                $resolved = $this->resolveMethod($subject, $parameters);
            }
        } else {
            $resolved = $this->resolveClass($subject, $parameters);
        }

        array_pop($this->buildStack);

        return $resolved;
    }

    public function resolveClass($class, array $parameters = [])
    {
        $reflectionClass = new ReflectionClass($class);
        $this->buildStack[] = $reflectionClass->getName();

        if (($reflectionMethod = $reflectionClass->getConstructor())) {
        	$reflectionParameters = $reflectionMethod->getParameters();

        	$resolvedParameters = $this->resolveParameters($reflectionParameters, $parameters);

            return $reflectionClass->newInstanceArgs($resolvedParameters);
        }
        if (!$reflectionClass->isInstantiable()) {
            throw new Exception("[$class] is not instantiable. Build stack : [".implode(', ', $this->buildStack)."]");
        }

        return $reflectionClass->newInstanceArgs();
    }

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

    public function resolveFunction($function, array $parameters = [])
    {
        $reflectionFunction = new ReflectionFunction($function);
        $reflectionParameters = $reflectionFunction->getParameters();
        $this->buildStack[] = $reflectionFunction->getName();

        $resolvedParameters = $this->resolveParameters($reflectionParameters, $parameters);

        return $reflectionFunction->invokeArgs($resolvedParameters);
    }

    private function resolveParameter(ReflectionParameter $parameter, array $parameters = [])
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

        try {
            return $this->resolve($parameter->name);
        } catch (Exception $e) {
            throw new Exception("Unresolvable dependency resolving [$parameter] in [".end($this->buildStack)."]");
        }
    }

    private function resolveParameters(array $reflectionParameters, array $parameters = [])
    {
        $dependencies = [];

        foreach ($reflectionParameters as $key => $parameter) {
            $dependencies[] = $this->resolveParameter($parameter, $parameters);
        }

        return self::mergeParameters($dependencies, $parameters);
    }

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
