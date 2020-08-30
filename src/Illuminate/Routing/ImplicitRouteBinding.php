<?php

namespace Illuminate\Routing;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Reflector;
use Illuminate\Support\Str;

class ImplicitRouteBinding
{
    /**
     * Resolve the implicit route bindings for the given route.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function resolveForRoute($container, $route)
    {
        foreach ($route->signatureParameters(UrlRoutable::class) as $parameter) {
            if (! $parameterName = static::getParameterName($parameter->getName(), $route->parameters())) {
                continue;
            }

            $model = static::resolveForRouteParameter(
                $container, $route, $parameterName, Reflector::getParameterClassName($parameter)
            );
            
            $route->setParameter($parameterName, $model);
        }
    }

    /**
     * Resolve the implicit route bindings for the given route parameter and type.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $parameterName
     * @param  string  $parameterClass
     * @return UrlRoutable
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function resolveForRouteParameter($container, $route, $parameterName, $parameterClass)
    {
        $parameterValue = $route->parameter($parameterName);

        if ($parameterValue instanceof UrlRoutable) {
            return $parameterValue;
        }

        $instance = $container->make($parameterClass);

        $parent = $route->parentOfParameter($parameterName);

        if ($parent instanceof UrlRoutable && in_array($parameterName, array_keys($route->bindingFields()))) {
            if (! $model = $parent->resolveChildRouteBinding(
                $parameterName, $parameterValue, $route->bindingFieldFor($parameterName)
            )) {
                throw (new ModelNotFoundException)->setModel(get_class($instance), [$parameterValue]);
            }
        } elseif (! $model = $instance->resolveRouteBinding($parameterValue, $route->bindingFieldFor($parameterName))) {
            throw (new ModelNotFoundException)->setModel(get_class($instance), [$parameterValue]);
        }

        return $model;
    }

    /**
     * Return the parameter name if it exists in the given parameters.
     *
     * @param  string  $name
     * @param  array  $parameters
     * @return string|null
     */
    protected static function getParameterName($name, $parameters)
    {
        if (array_key_exists($name, $parameters)) {
            return $name;
        }

        if (array_key_exists($snakedName = Str::snake($name), $parameters)) {
            return $snakedName;
        }
    }
}
