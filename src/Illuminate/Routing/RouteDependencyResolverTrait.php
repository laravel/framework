<?php

namespace Illuminate\Routing;

use ReflectionMethod;
use ReflectionParameter;
use Illuminate\Support\Arr;
use ReflectionFunctionAbstract;

trait RouteDependencyResolverTrait
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
        $originalParameters = $parameters;

        foreach ($reflector->getParameters() as $key => $parameter) {
            $instance = $this->transformDependency(
                $parameter, $parameters, $originalParameters
            );

            if (! is_null($instance)) {
                $this->spliceIntoParameters($parameters, $key, $instance);
            }
        }

        return $parameters;
    }

    /**
     * Attempt to transform the given parameter into a class instance.
     *
     * @param  \ReflectionParameter  $parameter
     * @param  array  $parameters
     * @param  array  $originalParameters
     * @return mixed
     */
    protected function transformDependency(ReflectionParameter $parameter, $parameters, $originalParameters)
    {
        $class = $parameter->getClass();

        // If the parameter has a type-hinted class, we will check to see if it is already in
        // the list of parameters. If it is we will just skip it as it is probably a model
        // binding and we do not want to mess with those; otherwise, we resolve it here.
        if ($class && ! $this->alreadyInParameters($class->name, $parameters)) {
            return $this->container->make($class->name);
        }
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
        return ! is_null(Arr::first($parameters, function ($value) use ($class) {
            return $value instanceof $class;
        }));
    }

    /**
     * Splice the given value into the parameter list.
     *
     * @param  array  $parameters
     * @param  string  $key
     * @param  mixed  $instance
     * @return void
     */
    protected function spliceIntoParameters(array &$parameters, $key, $instance)
    {
        array_splice(
            $parameters, $key, 0, [$instance]
        );
    }
}
