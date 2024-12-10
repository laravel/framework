<?php

namespace Illuminate\Routing;

use Illuminate\Container\Util;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Reflector;
use ReflectionClass;
use ReflectionException;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use stdClass;

trait ResolvesRouteDependencies
{
    /**
     * Resolve the object method's type-hinted dependencies.
     *
     * @param  array  $parameters
     * @param  object  $instance
     * @param  string  $method
     * @return array
     */
    protected function resolveClassMethodDependencies(array $parameters, $instance, $method)
    {
        if (! method_exists($instance, $method)) {
            return $parameters;
        }

        return $this->resolveMethodDependencies(
            $parameters, new ReflectionMethod($instance, $method)
        );
    }

    /**
     * Resolve the given method's type-hinted dependencies.
     *
     * @param  array  $parameters
     * @param  \ReflectionFunctionAbstract  $reflector
     * @return array
     */
    public function resolveMethodDependencies(array $parameters, ReflectionFunctionAbstract $reflector)
    {
        $instanceCount = 0;

        $values = array_values($parameters);

        $skippableValue = new stdClass;

        $resolvedInterfaces = [];

        foreach ($reflector->getParameters() as $key => $parameter) {
            $className = Reflector::getParameterClassName($parameter);
            $instance = $this->transformDependency($parameter, $parameters, $className, $skippableValue, $resolvedInterfaces);

            if ($instance !== $skippableValue && ! $this->alreadyInResolvedInterfaces($className, $resolvedInterfaces)) {
                $resolvedInterfaces[] = $className;
            }

            if ($instance !== $skippableValue) {
                $instanceCount++;

                $this->spliceIntoParameters($parameters, $key, $instance);
            } elseif (! isset($values[$key - $instanceCount]) &&
                $parameter->isDefaultValueAvailable()) {
                $this->spliceIntoParameters($parameters, $key, $parameter->getDefaultValue());
            }

            $this->container->fireAfterResolvingAttributeCallbacks($parameter->getAttributes(), $instance);
        }

        return $parameters;
    }

    /**
     * Attempt to transform the given parameter into a class instance.
     *
     * @param  ReflectionParameter  $parameter
     * @param  array  $parameters
     * @param  $className
     * @param  object  $skippableValue
     * @param  $resolvedInterfaces
     * @return mixed
     *
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    protected function transformDependency(ReflectionParameter $parameter, $parameters, $className, object $skippableValue, $resolvedInterfaces)
    {
        if ($attribute = Util::getContextualAttributeFromDependency($parameter)) {
            return $this->container->resolveFromAttribute($attribute);
        }

        if ($this->shouldResolveInterface($className, $parameters, $resolvedInterfaces)) {
            return $this->container->make($className);
        }

        // If the parameter has a type-hinted class, we will check to see if it is already in
        // the list of parameters. If it is we will just skip it as it is probably a model
        // binding and we do not want to mess with those; otherwise, we resolve it here.
        if ($className && (! $this->alreadyInParameters($className, $parameters))) {
            $isEnum = (new ReflectionClass($className))->isEnum();

            return $parameter->isDefaultValueAvailable()
                ? ($isEnum ? $parameter->getDefaultValue() : null)
                : $this->container->make($className);
        }

        return $skippableValue;
    }

    /**
     * Determine if an object of the given class is in a list of parameters.
     *
     * @param  string  $class
     * @param  array  $parameters
     * @return bool
     */
    protected function alreadyInParameters($class, array $parameters)
    {
        return ! is_null(Arr::first($parameters, fn ($value) => $value instanceof $class));
    }

    protected function alreadyInResolvedInterfaces($class, array $resolvedInterfaces)
    {
        return in_array($class, $resolvedInterfaces);
    }

    /**
     * Determines if an interface should be resolved, even if
     * a concrete class that implements it already exists
     * in the list of parameters.
     *
     * @param  $className
     * @param  array  $parameters
     * @param  $resolvedInterfaces
     * @return bool
     */
    protected function shouldResolveInterface($className, array $parameters, $resolvedInterfaces): bool
    {
        return $className &&
            $this->alreadyInParameters($className, $parameters) &&
            interface_exists($className) &&
            ! $this->alreadyInResolvedInterfaces($className, $resolvedInterfaces) &&
            (new ReflectionClass($className))->isInterface();
    }

    /**
     * Splice the given value into the parameter list.
     *
     * @param  array  $parameters
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    protected function spliceIntoParameters(array &$parameters, $offset, $value)
    {
        array_splice(
            $parameters, $offset, 0, [$value]
        );
    }
}
