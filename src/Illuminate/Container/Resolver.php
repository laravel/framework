<?php

namespace Illuminate\Container;

use Closure;
use Reflector;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;

class Resolver
{
    protected $buildStack = [];

    public function resolve($subject, array $parameters = [])
    {
        if (is_callable($subject)) {
            if (is_string($subject) || $subject instanceof Closure) {
                $resolved = $this->resolveFunction($subject, $parameters);
            } else {
                $resolved = $this->resolveMethod($subject, $parameters);
            }
        } else {
            $resolved = $this->resolveClass($subject, $parameters);
        }

        $this->buildStack = [];

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
        $reflectionMethod = new ReflectionMethod($method[0], $method[1]);
        $reflectionParameters = $reflectionMethod->getParameters();
        $this->buildStack[] = $reflectionMethod->getName();

        $resolvedParameters = $this->resolveParameters($reflectionParameters, $parameters);

        return $reflectionMethod->invokeArgs($method[0], $resolvedParameters);
    }

    public function resolveFunction($function, array $parameters = [])
    {
        $reflectionFunction = new ReflectionFunction($function);
        $reflectionParameters = $reflectionFunction->getParameters();
        $this->buildStack[] = $reflectionFunction->getName();

        $resolvedParameters = $this->resolveParameters($reflectionParameters, $parameters);

        return $reflectionFunction->invokeArgs($resolvedParameters);
    }

    private function resolveParameters(array $reflectionParameters, array $parameters = [])
    {
        $dependencies = [];

        foreach ($reflectionParameters as $key => $parameter) {
            if (isset($parameters[$key])) {
                $dependencies[] = $parameters[$key];

                unset($parameters[$key]);
            } else if (isset($parameters[$parameter->name])) {
                $dependencies[] = $parameters[$parameter->name];

                unset($parameters[$parameter->name]);
            } else if ($parameter->getClass()) {
                $dependencies[] = $this->resolve($parameter->getClass()->name);
            } else if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new Exception("Unresolvable dependency resolving [$parameter] in [".end($this->buildStack)."]");
            }
        }

        return array_merge($dependencies, $parameters);
    }
}
