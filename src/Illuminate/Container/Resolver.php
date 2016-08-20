<?php

namespace Illuminate\Container;

use Reflector;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;

class Resolver
{

	private function resolveParameters(Reflector $reflector, array $parameters = [])
    {
        $i = 0;
        $resolvedParameters = [];

        foreach ($reflector->getParameters() as $parameter) {
            if (($class = $parameter->getClass())) {
                $resolvedParameters[] = $this->resolve($class->getName());
            } else if ($parameter->isDefaultValueAvailable())  {
                $resolvedParameters[] = $parameter->getDefaultValue();
            } else {
                $resolvedParameters[] = $parameters[$i++];
            }
        }

        return $resolvedParameters;
    }

    private function resolveClass($class, array $parameters = [])
    {
        $reflectionClass = new ReflectionClass($class);
        $constructor = $reflectionClass->getConstructor();

        if ($constructor) {
            $resolvedParameters = $this->resolveParameters($constructor, $parameters);
        } else {
            $resolvedParameters = [];
        }

        return $reflectionClass->newInstanceArgs($resolvedParameters);
    }

    private function resolveMethod($method, array $parameters = [])
    {
        $reflectionMethod = new ReflectionMethod($method[0], $method[1]);
        $resolvedParameters = $this->resolveParameters($reflectionMethod, $parameters);

        return $reflectionMethod->invokeArgs($method[0], $resolvedParameters);
    }

    private function resolveFunction($function, array $parameters = [])
    {
        $reflectionFunction = new ReflectionFunction($function);
        $resolvedParameters = $this->resolveParameters($reflectionFunction, $parameters);

        return $reflectionFunction->invokeArgs($resolvedParameters);
    }

    public function resolve($abstract, array $parameters = [])
    {
        if (is_callable($abstract) && is_string($abstract)) {
            return $this->resolveFunction($abstract, $parameters);
        } else if (is_callable($abstract) && is_array($abstract)) {
            return $this->resolveMethod($abstract, $parameters);
        } else if (is_string($abstract)) {
            return $this->resolveClass($abstract, $parameters);
        }

        return null;
    }
}
