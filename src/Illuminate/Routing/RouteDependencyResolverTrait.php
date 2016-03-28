<?php

namespace Illuminate\Routing;

use ReflectionMethod;
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
        $originalParameters = $parameters;
        $parameters = array_values($parameters);

        foreach ($reflector->getParameters() as $key => $parameter) {
            $class = $parameter->getClass();
            if ($class) {
                if (array_key_exists($key, $parameters) && $parameters[$key] instanceof $class->name) {
                    continue;
                } else {
                    $instance = $this->container->make($class->name);
                    if (array_key_exists($key, $parameters)) {
                        $this->spliceIntoParameters($parameters, $key, $instance);
                    } else {
                        $parameters[$key] = $instance;
                    }
                }
            } else {
                if (! array_key_exists($key, $parameters)) {
                    $parameters[$key] = $parameter->getDefaultValue();
                }
            }
        }

        return $parameters;
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
