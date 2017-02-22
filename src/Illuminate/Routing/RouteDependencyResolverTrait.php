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
     * Resolve the given method's type-hinted dependencies and other parameters.
     *
     * @param  array  $parameters
     * @param  \ReflectionFunctionAbstract  $reflector
     * @return array
     */
    public function resolveMethodDependencies(array $parameters, ReflectionFunctionAbstract $reflector)
    {
        $arguments = [];
        $classNames = [];

        // Loop through all method arguments injecting any dependencies or setting default values
        foreach ($reflector->getParameters() as $key => $parameter) {
            $instance = $this->transformDependency($parameter, $parameters);

            // If no dependency or default value $instance should be null
            if (is_object($instance)) {
                $classNames[] = get_class($instance);
            } elseif ($parameter->isDefaultValueAvailable()) {
                $instance = $parameter->getDefaultValue();
            }

            $arguments[] = $instance;
        }

        // Remove all found dependencies from $parameters array,
        // this way we can inject all non object parameters in their respective order
        $parameters = $this->cleanDependenciesFromParameters($parameters, $classNames);

        if (! empty($parameters)) {
            return $this->mergeRemainingParameters($arguments, $parameters);
        }

        return $arguments;
    }

    /**
     * Attempt to transform the given parameter into a class instance.
     *
     * @param  \ReflectionParameter  $parameter
     * @param  array  $parameters
     * @return mixed
     */
    protected function transformDependency(ReflectionParameter $parameter, array $parameters)
    {
        // If the current parameter shouldn't be a class this method will return null
        if ($class = $parameter->getClass()) {

            // If the parameter has a type-hinted class, we will check to see if it is already in
            // the list of parameters. If it is we return that class; otherwise, we resolve it here.
            if ($this->alreadyInParameters($class->name, $parameters)) {
                return $this->fetchParameterByClassName($class->name, $parameters);
            }

            return $this->container->make($class->name);
        }
    }

    /**
     * Remove known classes from an array of parameters.
     *
     * @param  array $originalParameters
     * @param  array $classNames
     * @return array
     */
    protected function cleanDependenciesFromParameters(array $originalParameters, array $classNames)
    {
        $parameters = [];
        // Remove any duplicates from class list
        $classNames = array_unique($classNames);

        // Create a new parameters array leaving out any classes from the class list
        foreach ($originalParameters as $key => $parameter) {
            if (! is_object($parameter) || ! in_array(get_class($parameter), $classNames)) {
                $parameters[$key] = $parameter;
            }
        }

        return $parameters;
    }

    /**
     * Merge route parameters into method arguments.
     *
     * @param  array $arguments
     * @param  array $parameters
     * @return array
     */
    protected function mergeRemainingParameters(array $arguments, array $parameters)
    {
        foreach ($arguments as $key => $argument) {
            // Arguments that aren't objects will be a default value or null
            // these values should be filled in order by the parameters
            if (! is_object($argument)) {
                $arguments[$key] = array_shift($parameters);
                if (empty($parameters)) {
                    break;
                }
            }
        }

        return $arguments;
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
        return ! is_null($this->fetchParameterByClassName($class, $parameters));
    }

    /**
     * Return a parameter by its class.
     *
     * @param  string $class
     * @param  array $parameters
     * @return mixed
     */
    protected function fetchParameterByClassName($class, array $parameters)
    {
        return Arr::first($parameters, function ($value) use ($class) {
            return $value instanceof $class;
        });
    }
}
