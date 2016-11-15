<?php

namespace Illuminate\Routing;

use ReflectionMethod;
use ReflectionParameter;
use Illuminate\Support\Arr;
use ReflectionFunctionAbstract;

trait RouteDependencyResolverTrait
{
    /**
     * Call a class method with the resolved dependencies.
     *
     * @param  object  $instance
     * @param  string  $method
     * @return mixed
     */
    protected function callWithDependencies($instance, $method)
    {
        return call_user_func_array(
            [$instance, $method], $this->resolveClassMethodDependencies([], $instance, $method)
        );
    }

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
        $originalParameters  = $parameters;
        // Is method have arguments
        if ($reflectorParameters = $reflector->getParameters()) {
            // Build parameters using arguments on the method using ReflectionMethod
            $parameters = array();
            foreach ($reflector->getParameters() as $key => $parameter) {
                // Is parameter valid ( not exist in $originalParameters )
                $parameterName = $parameter->getName();
                if (!array_key_exists($parameterName, $originalParameters)) {
                    if ($ReflectionClass = $parameter->getClass()) {
                        // Set default value for argument with Type Hinting
                        $className    = $ReflectionClass->name;
                        $defaultValue = $this->container->make($className);
                    } else {
                        // Set default value for argument without Type Hinting
                        $defaultValue = ($parameter->isDefaultValueAvailable()) ? $parameter->getDefaultValue() : null;
                    }
                } else {
                    $defaultValue = $originalParameters[$parameterName];
                }
                // Add parameter to parameters
                $parameters[$parameterName] = $defaultValue;
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
        return ! is_null(Arr::first($parameters, function ($key, $value) use ($class) {
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
