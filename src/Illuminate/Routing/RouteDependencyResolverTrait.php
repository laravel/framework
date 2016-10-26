<?php

namespace Illuminate\Routing;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Illuminate\Support\Arr;
use ReflectionFunctionAbstract;
use Illuminate\Database\Eloquent\Model;

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
     * Determine if the given type-hinted class is an implict Eloquent binding.
     *
     * Must not already be resolved in the parameter list by an explicit model binding.
     *
     * @param  \ReflectionClass  $class
     * @param  array  $parameters
     * @return bool
     */
    protected function vacantEloquentParameter(ReflectionClass $class, array $parameters)
    {
        return $class->isSubclassOf(Model::class) &&
             ! $this->alreadyInParameters($class->name, $parameters);
    }

    /**
     * Extract an implicit model binding's key out of the parameter list.
     *
     * @param  \ReflectionParameter  $parameter
     * @param  array  $originalParameters
     *
     * @return mixed
     */
    protected function extractModelIdentifier(ReflectionParameter $parameter, array $originalParameters)
    {
        return Arr::first($originalParameters, function ($parameterKey) use ($parameter) {
            return $parameterKey === $parameter->name;
        });
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
