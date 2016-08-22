<?php

namespace Illuminate\Container;

use Closure;
use Reflector;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;

class Resolver
{
	const TYPE_CLASS = 1;
	const TYPE_METHOD = 2;
	const TYPE_FUNCTION = 3;

    public function resolve($subject, array $parameters = [])
    {
        if (is_callable($subject)) {
            if (is_string($subject) || $subject instanceof Closure) {
                return $this->resolveFunction($subject, $parameters);
            }

            return $this->resolveMethod($subject, $parameters);
        }

        return $this->resolveClass($subject, $parameters);
    }

    public function resolveClass($class, array $parameters = [])
    {
        $reflectionClass = new ReflectionClass($class);

        if (($reflectionMethod = $reflectionClass->getConstructor())) {
        	$reflectionParameters = $reflectionMethod->getParameters();

        	$resolvedParameters = $this->resolveParameters($reflectionParameters, $parameters);

        	return $reflectionClass->newInstanceArgs($resolvedParameters);
        }

        return $reflectionClass->newInstanceArgs();
    }

    public function resolveMethod($method, array $parameters = [])
    {
        $reflectionMethod = new ReflectionMethod($method[0], $method[1]);
        $reflectionParameters = $reflectionMethod->getParameters();

        $resolvedParameters = $this->resolveParameters($reflectionParameters, $parameters);

        return $reflectionMethod->invokeArgs($method[0], $resolvedParameters);
    }

    public function resolveFunction($function, array $parameters = [])
    {
        $reflectionFunction = new ReflectionFunction($function);
        $reflectionParameters = $reflectionFunction->getParameters();

        $resolvedParameters = $this->resolveParameters($reflectionParameters, $parameters);

        return $reflectionFunction->invokeArgs($resolvedParameters);
    }

    private function resolveParameters(array $reflectionParameters, array $parameters = [])
    {
        $dependencies = [];

        foreach ($reflectionParameters as $key => $parameter) {
            if (array_key_exists($key, $parameters)) {
                $dependencies[] = $parameters[$key];

                unset($parameters[$key]);
            } else if (array_key_exists($parameter->name, $parameters)) {
                $dependencies[] = $parameters[$parameter->name];

                unset($parameters[$parameters[$parameter->name]]);
            } else if ($parameter->getClass()) {
                $dependencies[] = $this->resolve($parameter->getClass()->name);
            } else if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            }
        }

        return array_merge($dependencies, $parameters);
    }
}
